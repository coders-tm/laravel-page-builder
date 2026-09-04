/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { createContext, useContext } from "react"
import type { Editor } from "./editor/Editor"

/**
 * React context for the Editor instance.
 *
 * Provides access to all editor managers, services, and the
 * event bus to any component in the tree via the useEditorInstance() hook.
 */
const EditorContext = createContext<Editor | null>(null)

/**
 * Provider component that makes the Editor instance
 * available to all child components.
 *
 * @example
 * <EditorProvider editor={editor}>
 *   <App />
 * </EditorProvider>
 */
export function EditorProvider({
  editor,
  children,
}: {
  editor: Editor
  children: React.ReactNode
}) {
  return <EditorContext.Provider value={editor}>{children}</EditorContext.Provider>
}

/**
 * Hook to access the Editor instance from context.
 *
 * This gives you the central editor object with all managers:
 *   editor.sections  — section CRUD
 *   editor.blocks    — block CRUD
 *   editor.selection — selection state and commands
 *   editor.layout    — layout/ui state manager
 *   editor.navigation — URL/navigation manager
 *   editor.bootstrap  — startup/page-load orchestration
 *   editor.shortcuts  — keyboard shortcut manager
 *   editor.interaction — iframe hover/focus manager
 *   editor.history   — undo/redo
 *   editor.preview   — iframe communication
 *   editor.pages     — page load/save/meta
 *   editor.config    — runtime config access
 *   editor.assets    — asset management
 *   editor.on()      — event subscription
 *
 * @throws Error if used outside of EditorProvider
 *
 * @example
 * const editor = useEditorInstance();
 * editor.sections.add('hero', heroSchema);
 * editor.on('section:added', ({ sectionId }) => { … });
 */
export function useEditorInstance(): Editor {
  const editor = useContext(EditorContext)
  if (!editor) {
    throw new Error("useEditorInstance must be used within an EditorProvider")
  }
  return editor
}

export default EditorContext
