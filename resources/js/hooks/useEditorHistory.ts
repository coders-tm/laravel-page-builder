import { useCallback, useRef } from "react"
import { useHistory } from "./useHistory"

interface UseEditorHistoryOptions {
  currentPage: any
  setCurrentPage: (page: any) => void
  renderFullPage: (snapshot: any) => void
}

/**
 * Manages undo/redo history for the editor, including snapshot recording
 * and state restoration.
 *
 * Wraps the generic useHistory hook with editor-specific logic:
 *   - Guards against duplicate snapshots during undo/redo restores
 *   - Re-renders the full preview when a snapshot is applied
 *   - Records snapshots on every page mutation
 */
export function useEditorHistory({
  currentPage,
  setCurrentPage,
  renderFullPage,
}: UseEditorHistoryOptions) {
  const history = useHistory()

  /**
   * Guard flag: when true, the snapshot callback ignores the currentPage
   * change because it was caused by an undo/redo restore, not a user edit.
   */
  const isRestoringRef = useRef(false)

  /** Record a new state snapshot into history. */
  const snapshot = useCallback(() => {
    if (currentPage) history.push(currentPage)
  }, [currentPage, history])

  /** Apply a restored snapshot back into the editor. */
  const applySnapshot = useCallback(
    (restoredState: any) => {
      if (!restoredState) return
      isRestoringRef.current = true
      setCurrentPage(restoredState)
      renderFullPage(restoredState)
    },
    [setCurrentPage, renderFullPage],
  )

  const handleUndo = useCallback(() => {
    applySnapshot(history.undo())
  }, [history, applySnapshot])

  const handleRedo = useCallback(() => {
    applySnapshot(history.redo())
  }, [history, applySnapshot])

  /**
   * Should be called inside a useEffect watching currentPage.
   * Returns true if the snapshot was recorded, false if it was
   * skipped (due to an active undo/redo restore).
   */
  const maybeSnapshot = useCallback(() => {
    if (isRestoringRef.current) {
      isRestoringRef.current = false
      return false
    }
    snapshot()
    return true
  }, [snapshot])

  return {
    canUndo: history.canUndo,
    canRedo: history.canRedo,
    handleUndo,
    handleRedo,
    maybeSnapshot,
    resetHistory: history.reset,
  }
}
