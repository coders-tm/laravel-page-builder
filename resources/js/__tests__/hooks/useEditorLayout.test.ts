/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, beforeEach } from "vitest"
import { renderHook, act } from "@testing-library/react"
import React from "react"
import { useEditorLayout } from "@/hooks/useEditorLayout"
import { EditorProvider } from "@/core/editorContext"
import { Editor } from "@/core/editor/Editor"

function wrapper(editor: Editor) {
  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(EditorProvider, { editor }, children)
}

describe("useEditorLayout", () => {
  it("returns the current layout snapshot", () => {
    const editor = new Editor()
    const { result } = renderHook(() => useEditorLayout(), { wrapper: wrapper(editor) })
    expect(result.current.device).toBe("desktop")
    expect(result.current.sidebarTab).toBe("sections")
    expect(result.current.inspectorEnabled).toBe(true)
  })

  it("updates when layout state changes", () => {
    const editor = new Editor()
    const { result } = renderHook(() => useEditorLayout(), { wrapper: wrapper(editor) })
    act(() => {
      editor.layout.setDevice("mobile")
    })
    expect(result.current.device).toBe("mobile")
  })

  it("updates sidebar tab when changed", () => {
    const editor = new Editor()
    const { result } = renderHook(() => useEditorLayout(), { wrapper: wrapper(editor) })
    act(() => {
      editor.layout.setSidebarTab("theme")
    })
    expect(result.current.sidebarTab).toBe("theme")
  })

  it("toggleInspector() updates inspectorEnabled via hook", () => {
    const editor = new Editor()
    const { result } = renderHook(() => useEditorLayout(), { wrapper: wrapper(editor) })
    act(() => {
      editor.layout.toggleInspector()
    })
    expect(result.current.inspectorEnabled).toBe(false)
  })
})
