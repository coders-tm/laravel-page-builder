import { useState, useCallback } from "react"
import { getNestedBlock } from "../core/store/useStore"

/**
 * Resolve the SettingSchema for a specific setting id on a block, following
 * the full blockPath through the section schema.  This mirrors the logic in
 * SettingsPanel.tsx `activeBlockSchema` so that locally-defined section block
 * schemas (not in the global theme-blocks registry) are also recognised.
 *
 * Returns `null` when the setting cannot be resolved.
 */
function resolveBlockSettingSchema(
  sectionInstance: any,
  sectionSchema: any,
  blockPath: string[],
  settingId: string,
  themeBlocks: Record<string, any>,
): any | null {
  if (!sectionInstance || !sectionSchema || blockPath.length === 0) return null

  // Expand block type stubs (bare refs → theme schema, local defs → as-is)
  function expandBlockTypes(
    rawBlocks: Array<{ type: string; name?: string; settings?: any[] }>,
  ): any[] {
    const result: any[] = []
    for (const b of rawBlocks || []) {
      if (b.type === "@theme") {
        Object.values(themeBlocks).forEach((bd: any) => result.push(bd.schema))
      } else {
        const themeSchema = themeBlocks[b.type]?.schema
        const hasExtra = b.name !== undefined || (b.settings && b.settings.length > 0)
        if (hasExtra || !themeSchema) {
          result.push(b)
        } else {
          result.push(themeSchema)
        }
      }
    }
    return result
  }

  let allowedTypes = expandBlockTypes((sectionSchema.blocks as any[]) || [])
  let currentSchema: any = null
  let currentInstances: Record<string, any> = sectionInstance.blocks || {}

  for (const blockId of blockPath) {
    const instance = currentInstances[blockId]
    if (!instance) return null

    const typeSchema = allowedTypes.find((t: any) => t.type === instance.type)
    if (!typeSchema) {
      currentSchema = themeBlocks[instance.type]?.schema || null
    } else {
      currentSchema = typeSchema
    }

    if (!currentSchema) return null

    currentInstances = instance.blocks || {}
    allowedTypes = expandBlockTypes((currentSchema.blocks as any[]) || [])
  }

  if (!currentSchema?.settings) return null
  return currentSchema.settings.find((s: any) => s.id === settingId) || null
}

interface AddSectionModalState {
  isOpen: boolean
  insertIndex: number | null
}

/**
 * Orchestrates all section/block CRUD operations.
 *
 * Delegates state mutations to usePageBuilder and preview
 * updates to usePreviewSync. The App component passes in
 * these dependencies so this hook stays focused on
 * coordinating actions and managing the add-section modal.
 */
export function useSectionActions({
  pageBuilder,
  navigation,
  previewSync,
}: {
  pageBuilder: any
  navigation: any
  previewSync: any
}) {
  const {
    currentPage,
    sections,
    blocks,
    addSection,
    removeSection,
    duplicateSection,
    reorderSections,
    addBlock,
    removeBlock,
    duplicateBlock,
    reorderBlocks,
    toggleSectionDisabled,
    toggleBlockDisabled,
    updateSectionSettings,
    updateBlockSettings,
    moveBlock,
  } = pageBuilder

  const { selectedSection, selectedBlock, setSection, clearSection, pushBlock } = navigation

  const {
    updateLiveText,
    debouncedRerender,
    removeFromPreview,
    reorderSectionsInPreview,
    reorderBlocksInPreview,
    toggleVisibilityInPreview,
  } = previewSync

  const [isReloading, setIsReloading] = useState(false)
  const [addSectionModal, setAddSectionModal] = useState<AddSectionModalState>({
    isOpen: false,
    insertIndex: null,
  })

  /**
   * Executes a settings update, determining if a live DOM update is possible
   * or if a full server-side re-render is required.
   */
  const executeSettingsUpdate = useCallback(
    (
      sectionId: string,
      entityId: string, // sectionId or blockId for live text mapping
      values: Record<string, any>,
      schemaResolver: (settingId: string) => any,
    ) => {
      const keys = Object.keys(values)

      // Optimization: If it's a single field update, we might be able to skip
      // the server re-render if it's a supported live-text field.
      if (keys.length === 1) {
        const settingId = keys[0]
        const value = values[settingId]

        // 1. Always try internal live update for string values (immediate feedback)
        if (typeof value === "string") {
          updateLiveText(`${entityId}.${settingId}`, value)
        }

        // 2. Decide if we can skip the expensive server re-render based on schema
        const setting = schemaResolver(settingId)
        const liveTypes = ["text", "textarea", "richtext"]

        if (setting && liveTypes.includes(setting.type)) {
          return // Skip re-render
        }
      }

      debouncedRerender(sectionId)
    },
    [updateLiveText, debouncedRerender],
  )

  /* ── Section actions ──────────────────────────────────────────────── */

  const handleUpdateSectionSettings = useCallback(
    (sectionId: string, values: Record<string, any>) => {
      updateSectionSettings(sectionId, values)

      executeSettingsUpdate(sectionId, sectionId, values, (settingId) => {
        const instance = currentPage?.sections?.[sectionId]
        const schema = instance ? sections[instance.type]?.schema : null
        return schema?.settings?.find((s: any) => s.id === settingId)
      })
    },
    [currentPage, sections, updateSectionSettings, executeSettingsUpdate],
  )

  const handleUpdateBlockSettings = useCallback(
    (
      sectionId: string,
      blockId: string,
      values: Record<string, any>,
      parentPath: string[] = [],
    ) => {
      updateBlockSettings(sectionId, blockId, values, parentPath)

      executeSettingsUpdate(sectionId, blockId, values, (settingId) => {
        const sectionInstance = currentPage?.sections?.[sectionId]
        if (!sectionInstance) return null

        // Build the full block path: [...parentPath, blockId]
        const fullPath = [...parentPath, blockId]

        // Resolve the setting schema by walking the section schema hierarchy.
        // This handles both locally-defined section block schemas AND global
        // theme-block schemas (the old fallback only checked the theme registry
        // which caused nested/local block settings to always trigger re-render).
        const sectionMeta = sections[sectionInstance.type]
        if (sectionMeta?.schema) {
          const setting = resolveBlockSettingSchema(
            sectionInstance,
            sectionMeta.schema,
            fullPath,
            settingId,
            blocks,
          )
          if (setting !== null) return setting
        }

        // Fallback: direct theme-block registry lookup (top-level blocks
        // whose type is registered globally).
        const blockInstance = getNestedBlock(sectionInstance.blocks, fullPath)
        const schema = blockInstance ? blocks[blockInstance.type]?.schema : null
        return schema?.settings?.find((s: any) => s.id === settingId) ?? null
      })
    },
    [currentPage, sections, blocks, updateBlockSettings, executeSettingsUpdate],
  )

  const openAddMenu = useCallback(
    (position: string | null = null, targetId: string | null = null) => {
      let insertIndex: number | null = null
      if (targetId && currentPage?.order) {
        const idx = currentPage.order.indexOf(targetId)
        if (idx !== -1) {
          insertIndex = position === "after" ? idx + 1 : idx
        }
      }
      setAddSectionModal({ isOpen: true, insertIndex })
    },
    [currentPage],
  )

  const closeAddMenu = useCallback(() => {
    setAddSectionModal({ isOpen: false, insertIndex: null })
  }, [])

  const handleAddSection = useCallback(
    (type: string, schema: any) => {
      const sectionId = addSection(type, schema, addSectionModal.insertIndex)
      closeAddMenu()
      setSection(sectionId)
      debouncedRerender(sectionId)
    },
    [addSection, addSectionModal.insertIndex, closeAddMenu, setSection, debouncedRerender],
  )

  const handleRemoveSection = useCallback(
    (sectionId: string) => {
      removeSection(sectionId)
      if (selectedSection === sectionId) clearSection()
      removeFromPreview(sectionId)
    },
    [removeSection, selectedSection, clearSection, removeFromPreview],
  )

  const handleDuplicateSection = useCallback(
    (sectionId: string) => {
      const newId = duplicateSection(sectionId)
      setSection(newId)
      debouncedRerender(newId)
    },
    [duplicateSection, setSection, debouncedRerender],
  )

  const handleReorderSections = useCallback(
    (fromIndex: number, toIndex: number) => {
      reorderSections(fromIndex, toIndex)
      // The section DOM elements already exist in the iframe — just reorder them
      // in-place without a costly full reload.
      //
      // fromIndex / toIndex are relative to the page-only sub-array (the
      // DnD context excludes layout sections).  Reconstruct the page-only
      // order after the move so we only send moveable IDs to the iframe.
      const sections = currentPage?.sections || {}
      const pageOnly = (currentPage?.order || []).filter(
        (id: string) => !(sections[id] as any)?.layout,
      )
      const newOrder = [...pageOnly]
      const [moved] = newOrder.splice(fromIndex, 1)
      newOrder.splice(toIndex, 0, moved)
      reorderSectionsInPreview(newOrder)
    },
    [reorderSections, reorderSectionsInPreview, currentPage],
  )

  const handleToggleSectionDisabled = useCallback(
    (sectionId: string) => {
      toggleSectionDisabled(sectionId)
      // Read the NEW disabled state directly from the store (Immer mutates in-place)
      // after the toggle so we send the correct value to the iframe.
      const newDisabled = !currentPage?.sections?.[sectionId]?.disabled
      toggleVisibilityInPreview("section", sectionId, newDisabled)
    },
    [toggleSectionDisabled, toggleVisibilityInPreview, currentPage],
  )

  /* ── Block actions ────────────────────────────────────────────────── */

  const handleAddBlock = useCallback(
    (
      sectionId: string,
      blockType: string,
      defaults: Record<string, any>,
      afterBlockId?: string | null,
      parentPath: string[] = [],
      initialBlocks?: Record<string, any>,
      initialOrder?: string[],
    ) => {
      const blockId = addBlock(
        sectionId,
        blockType,
        defaults,
        afterBlockId,
        parentPath,
        initialBlocks,
        initialOrder,
      )
      if (parentPath.length > 0) {
        // Push child block onto the path (section > ... > parent > newBlock)
        pushBlock(blockId)
      } else {
        setSection(sectionId, blockId)
      }
      debouncedRerender(sectionId)
    },
    [addBlock, setSection, pushBlock, debouncedRerender],
  )

  const handleRemoveBlock = useCallback(
    (sectionId: string, blockId: string, parentPath: string[] = []) => {
      removeBlock(sectionId, blockId, parentPath)
      if (selectedBlock === blockId) setSection(sectionId)
      debouncedRerender(sectionId)
    },
    [removeBlock, selectedBlock, setSection, debouncedRerender],
  )

  const handleDuplicateBlock = useCallback(
    (sectionId: string, blockId: string, parentPath: string[] = []) => {
      duplicateBlock(sectionId, blockId, parentPath)
      debouncedRerender(sectionId)
    },
    [duplicateBlock, debouncedRerender],
  )

  const handleReorderBlocks = useCallback(
    (sectionId: string, newOrder: string[], parentPath: string[] = []) => {
      reorderBlocks(sectionId, newOrder, parentPath)
      // Re-append block DOM nodes in the new order — no server round-trip needed.
      // The parent block ID is the last segment of parentPath (undefined for
      // top-level section blocks).
      const parentBlockId = parentPath.length > 0 ? parentPath[parentPath.length - 1] : null
      reorderBlocksInPreview(sectionId, newOrder, parentBlockId)
    },
    [reorderBlocks, reorderBlocksInPreview],
  )

  const handleToggleBlockDisabled = useCallback(
    (sectionId: string, blockId: string, parentPath: string[] = []) => {
      toggleBlockDisabled(sectionId, blockId, parentPath)
      // Derive the new disabled value from the store state after the toggle.
      const section = currentPage?.sections?.[sectionId]
      const parentBlock =
        parentPath.length > 0 ? getNestedBlock(section?.blocks, parentPath) : section
      const newDisabled = !parentBlock?.blocks?.[blockId]?.disabled
      toggleVisibilityInPreview("block", sectionId, newDisabled, blockId)
    },
    [toggleBlockDisabled, toggleVisibilityInPreview, currentPage],
  )

  const handleMoveBlock = useCallback(
    (
      fromSectionId: string,
      toSectionId: string,
      blockId: string,
      fromPath: string[],
      toPath: string[],
      toIndex: number,
    ) => {
      moveBlock(fromSectionId, toSectionId, blockId, fromPath, toPath, toIndex)
      // Re-render the affected sections. If the block moved between sections
      // both need to be refreshed; if within the same section, one call suffices.
      debouncedRerender(fromSectionId)
      if (toSectionId !== fromSectionId) {
        debouncedRerender(toSectionId)
      }
    },
    [moveBlock, debouncedRerender],
  )

  return {
    isReloading,
    setIsReloading,
    addSectionModal,
    openAddMenu,
    closeAddMenu,
    handleUpdateSectionSettings,
    handleUpdateBlockSettings,
    handleAddSection,
    handleRemoveSection,
    handleDuplicateSection,
    handleReorderSections,
    handleToggleSectionDisabled,
    handleAddBlock,
    handleRemoveBlock,
    handleDuplicateBlock,
    handleReorderBlocks,
    handleMoveBlock,
    handleToggleBlockDisabled,
  }
}
