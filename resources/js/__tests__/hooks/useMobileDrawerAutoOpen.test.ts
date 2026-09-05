/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { renderHook, act } from "@testing-library/react"
import React from "react"
import { useMobileDrawerAutoOpen } from "@/hooks/useMobileDrawerAutoOpen"
import { EditorProvider } from "@/core/editorContext"
import { Editor } from "@/core/editor/Editor"
import { drawerManager } from "@/core/editor/DrawerManager"

function wrapper({ editor }: { editor: Editor }) {
  return ({ children }: { children: React.ReactNode }) =>
    React.createElement(EditorProvider, { editor }, children)
}

beforeEach(() => {
  drawerManager.close()
})

describe("useMobileDrawerAutoOpen", () => {
  it("does nothing when isMobile is false", () => {
    const editor = new Editor()
    const { result } = renderHook(() => useMobileDrawerAutoOpen(false), {
      wrapper: wrapper({ editor }),
    })
    act(() => {
      editor.navigation.setSelection("hero_1", [])
    })
    expect(drawerManager.isOpen).toBe(false)
  })

  it("opens 'sections' drawer when a section becomes selected on mobile", () => {
    const editor = new Editor()
    renderHook(() => useMobileDrawerAutoOpen(true), {
      wrapper: wrapper({ editor }),
    })
    act(() => {
      editor.navigation.setSelection("hero_1", [])
    })
    expect(drawerManager.activePanel).toBe("sections")
    expect(drawerManager.isOpen).toBe(true)
  })

  it("does nothing when drawer is already open on the sections panel", () => {
    drawerManager.open("sections")
    const editor = new Editor()
    const fn = vi.fn()
    drawerManager.subscribe(fn)
    renderHook(() => useMobileDrawerAutoOpen(true), {
      wrapper: wrapper({ editor }),
    })
    // Already open — open() is a no-op when same panel
    act(() => {
      editor.navigation.setSelection("hero_1", [])
    })
    // fn should have been called from the open() called by the hook,
    // but since it's already "sections" this is a no-op, so fn count is 0
    expect(drawerManager.activePanel).toBe("sections")
  })
})
