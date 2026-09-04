/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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
