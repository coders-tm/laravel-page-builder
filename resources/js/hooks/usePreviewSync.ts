import { useCallback, useState } from "react"
import api from "@/services/api"
import { useDebounce } from "./useDebounce"
import { IMessageBus } from "@/core/messaging/MessageBus"
import { useStore } from "@/core/store/useStore"

/**
 * Manages live preview synchronization with the iframe.
 *
 * Responsibilities:
 * - Re-rendering individual sections via the API
 * - Sending messages via IMessageBus
 * - Debouncing re-render calls
 * - Full page re-rendering for undo/redo
 */
export function usePreviewSync(currentPage: any, messageBus: IMessageBus | null) {
  const { currentSlug } = useStore()
  const [isSyncing, setIsSyncing] = useState(false)

  /** Render a single section via the API and push the HTML into the iframe. */
  const triggerRerender = useCallback(
    async (sectionId: string) => {
      if (!currentPage || !sectionId || !messageBus) return

      const sec = currentPage.sections?.[sectionId]
      if (!sec) return

      setIsSyncing(true)
      try {
        const { html } = await api.renderSection({
          slug: currentSlug,
          section_id: sectionId,
          section_type: sec.type,
          settings: sec.settings || {},
          blocks: sec.blocks || {},
          order: sec.order || [],
        })

        messageBus.send("update-section-html", {
          sectionId,
          html,
          // Pass only page-section IDs (no layout section IDs) so
          // the iframe can position new sections correctly.
          pageOrder: (currentPage.order || []).filter(
            (id: string) => !currentPage.sections?.[id]?.layout,
          ),
        })

        // Enforce ordering in case a section was just appended
        messageBus.send("reorder-sections", {
          order: (currentPage.order || []).filter(
            (id: string) => !currentPage.sections?.[id]?.layout,
          ),
        })
      } catch {
        /* silently fail — preview will refresh on save */
      } finally {
        setIsSyncing(false)
      }
    },
    [currentPage, messageBus],
  )

  const debouncedRerender = useDebounce(triggerRerender, 400)

  /** Tell the iframe to remove a section from the DOM. */
  const removeFromPreview = useCallback(
    (sectionId: string) => {
      messageBus?.send("remove-section", { sectionId })
    },
    [messageBus],
  )

  /**
   * Tell the iframe to reorder existing section DOM elements without
   * a full page reload. The sections are already rendered — this just
   * re-appends them in the new order.
   */
  const reorderSectionsInPreview = useCallback(
    (order: string[]) => {
      messageBus?.send("reorder-sections", { order })
    },
    [messageBus],
  )

  /**
   * Tell the iframe to reorder block DOM nodes within their parent container
   * without any server round-trip. Blocks are already in the DOM — this just
   * re-appends them in the new order inside the correct parent element.
   *
   * @param sectionId     - the section that owns the blocks
   * @param order         - new ordered list of block IDs
   * @param parentBlockId - optional; ID of a container block (e.g. a row)
   *                        whose direct children are being reordered.
   *                        When absent the section root is used as the parent.
   */
  const reorderBlocksInPreview = useCallback(
    (sectionId: string, order: string[], parentBlockId?: string | null) => {
      messageBus?.send("reorder-blocks", {
        sectionId,
        order,
        parentBlockId: parentBlockId ?? null,
      })
    },
    [messageBus],
  )

  /** Tell the iframe to reload the entire preview. */
  const reloadPreview = useCallback(() => {
    messageBus?.send("reload-preview")
  }, [messageBus])

  /** Update a specific text element in the iframe instantly without re-rendering. */
  const updateLiveText = useCallback(
    (path: string, value: string) => {
      messageBus?.send("update-live-text", { path, value })
    },
    [messageBus],
  )

  /**
   * Instantly toggle the visible/hidden state of a section or block in the
   * canvas by sending a CSS-class-only update.  No server round-trip needed.
   */
  const toggleVisibilityInPreview = useCallback(
    (kind: "section" | "block", sectionId: string, disabled: boolean, blockId?: string) => {
      messageBus?.send("toggle-visibility", {
        kind,
        sectionId,
        blockId: blockId ?? null,
        disabled,
      })
    },
    [messageBus],
  )

  /**
   * Re-render the entire page in the iframe from local state.
   *
   * Used after undo/redo — instead of reloading from the backend,
   * this removes stale sections and re-renders every section from
   * the provided page snapshot so the preview matches local state.
   */
  const renderFullPage = useCallback(
    async (pageSnapshot: any) => {
      if (!pageSnapshot || !messageBus) return

      setIsSyncing(true)
      const sections = pageSnapshot.sections || {}

      // Only page sections belong in the iframe — layout sections are
      // rendered by the layout blade template and must not be touched.
      const order: string[] = (pageSnapshot.order || []).filter(
        (id: string) => !sections[id]?.layout,
      )

      // Collect layout section IDs so the iframe can protect them
      // from removal during replace-all-sections.
      const layoutIds: string[] = (pageSnapshot.order || []).filter(
        (id: string) => !!sections[id]?.layout,
      )

      // 1) Remove sections from the DOM that are no longer in the snapshot
      messageBus.send("replace-all-sections", { order, layoutIds })

      // 2) Re-render every section in the correct order
      for (const sectionId of order) {
        const sec = sections[sectionId]
        if (!sec) continue

        try {
          const { html } = await api.renderSection({
            slug: currentSlug,
            section_id: sectionId,
            section_type: sec.type,
            settings: sec.settings || {},
            blocks: sec.blocks || {},
            order: sec.order || [],
          })

          messageBus.send("update-section-html", {
            sectionId,
            html,
            pageOrder: order,
          })
        } catch {
          /* silently fail for individual sections */
        }
      }

      // 3) Enforce final ordering
      messageBus.send("reorder-sections", { order })
      setIsSyncing(false)
    },
    [messageBus, currentSlug],
  )

  return {
    debouncedRerender,
    updateLiveText,
    toggleVisibilityInPreview,
    removeFromPreview,
    reorderSectionsInPreview,
    reorderBlocksInPreview,
    reloadPreview,
    renderFullPage,
    isSyncing,
  }
}
