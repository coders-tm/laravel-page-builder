/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { useEffect } from "react"
import { useStore } from "@/core/store/useStore"
import { useEditorInstance } from "@/core/editorContext"
import { useEditorNavigation } from "./useEditorNavigation"

/**
 * Root orchestration hook — sets up editor side-effects that must
 * run exactly once at the application root:
 *   • Bootstrap (load pages, sections, blocks, sync route)
 *   • History snapshots on every page mutation
 *   • Keyboard shortcuts mount/unmount
 *
 * All data is accessed directly by leaf components via useEditorInstance(),
 * useEditorLayout(), useEditorNavigation(), and useStore().
 */
export function useEditor() {
  const editor = useEditorInstance()

  // Register the router adapter (must be done at root level only).
  const { slug } = useEditorNavigation({ registerAdapter: true })
  const { pages, currentPage, loading } = useStore()

  // ── Bootstrap ──────────────────────────────────────────────────────
  useEffect(() => {
    void editor.bootstrap.loadInitialData()
  }, [editor])

  useEffect(() => {
    void editor.bootstrap.syncRoute(slug, pages)
  }, [editor, slug, pages])

  // ── History snapshots ──────────────────────────────────────────────
  useEffect(() => {
    if (!currentPage) return
    editor.history.maybeSnapshot(currentPage)
  }, [currentPage, editor])

  // ── Keyboard shortcuts ─────────────────────────────────────────────
  useEffect(() => {
    editor.shortcuts.configure({
      onUndo: () => editor.undo(),
      onRedo: () => editor.redo(),
      onInspectorToggle: () => editor.layout.toggleInspector(),
    })
  }, [editor])

  useEffect(() => {
    editor.shortcuts.mount()
    return () => editor.shortcuts.unmount()
  }, [editor])

  return { loading }
}
