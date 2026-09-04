/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { useStore } from "@/core/store/useStore"
import type { EventBus } from "./EventBus"

interface SelectionAdapter {
  setSelection?: (sectionId: string | null, blockPath: string[]) => void
  clearSelection?: () => void
}

/**
 * SelectionManager — centralizes section/block selection state.
 *
 * The canonical state is kept in the store; an optional adapter can
 * mirror selections to the URL (React Router) so deep-linking stays intact.
 */
export class SelectionManager {
  private adapter: SelectionAdapter | null = null

  constructor(private events: EventBus) {}

  setAdapter(adapter: SelectionAdapter | null): void {
    this.adapter = adapter
  }

  getSectionId(): string | null {
    return useStore.getState().selectedSection
  }

  getBlockId(): string | null {
    return useStore.getState().selectedBlock
  }

  getBlockPath(): string[] {
    return useStore.getState().selectedBlockPath ?? []
  }

  /**
   * Sync selection coming from external systems (e.g. URL params).
   * This updates store + emits events, but never writes back to adapter.
   */
  syncFromExternal(sectionId: string | null, blockPath: string[] = []): void {
    this.applySelection(sectionId, blockPath, false)
  }

  selectSection(sectionId: string | null): void {
    this.applySelection(sectionId, [], true)
  }

  selectBlock(sectionId: string, blockPath: string[]): void {
    this.applySelection(sectionId, blockPath, true)
  }

  clear(): void {
    this.applySelection(null, [], true)
  }

  private applySelection(
    sectionId: string | null,
    blockPath: string[] = [],
    syncAdapter: boolean,
  ): void {
    const state = useStore.getState()
    const nextPath = [...blockPath]
    const nextBlock = nextPath.length > 0 ? nextPath[nextPath.length - 1] : null

    const prevSection = state.selectedSection
    const prevPath = state.selectedBlockPath ?? []
    const prevBlock = state.selectedBlock

    const changed =
      prevSection !== sectionId ||
      prevBlock !== nextBlock ||
      prevPath.join(",") !== nextPath.join(",")

    if (!changed) return

    state.setSelectedSection(sectionId)
    state.setSelectedBlock(nextBlock)
    state.setSelectedBlockPath(nextPath)

    if (syncAdapter && this.adapter) {
      if (sectionId === null) {
        if (this.adapter.clearSelection) {
          this.adapter.clearSelection()
        } else {
          this.adapter.setSelection?.(null, [])
        }
      } else {
        this.adapter.setSelection?.(sectionId, nextPath)
      }
    }

    this.events.emit("selection:section-changed", { sectionId })

    if (sectionId === null && nextPath.length === 0) {
      this.events.emit("selection:cleared", {})
      return
    }

    this.events.emit("selection:block-changed", {
      sectionId,
      blockPath: nextPath,
    })
  }
}
