import { useMemo, useSyncExternalStore } from "react"
import { useEditorInstance } from "@/core/editorContext"
import type { SidebarTab } from "@/core/editor/LayoutManager"

export type { SidebarTab } from "@/core/editor/LayoutManager"

/**
 * React adapter for the class-based LayoutManager.
 */
export function useEditorLayout() {
  const editor = useEditorInstance()

  const version = useSyncExternalStore(
    (listener) => editor.layout.subscribe(listener),
    () => editor.layout.getVersion(),
    () => 0,
  )

  return useMemo(() => editor.layout.getSnapshot(), [editor, version])
}
