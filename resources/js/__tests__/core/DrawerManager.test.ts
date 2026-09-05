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
import { DrawerManager, drawerManager } from "@/core/editor/DrawerManager"

describe("DrawerManager", () => {
  let dm: DrawerManager

  beforeEach(() => {
    dm = new DrawerManager()
  })

  it("is closed by default", () => {
    expect(dm.isOpen).toBe(false)
    expect(dm.activePanel).toBeNull()
  })

  it("open() sets the active panel and marks isOpen as true", () => {
    dm.open("sections")
    expect(dm.isOpen).toBe(true)
    expect(dm.activePanel).toBe("sections")
  })

  it("open() is a no-op when the same panel is already active", () => {
    dm.open("sections")
    const v = dm.getVersion()
    dm.open("sections")
    expect(dm.getVersion()).toBe(v)
  })

  it("close() sets activePanel to null and isOpen to false", () => {
    dm.open("page")
    dm.close()
    expect(dm.isOpen).toBe(false)
    expect(dm.activePanel).toBeNull()
  })

  it("close() is a no-op when already closed", () => {
    const v = dm.getVersion()
    dm.close()
    expect(dm.getVersion()).toBe(v)
  })

  it("toggle() opens a closed panel", () => {
    dm.toggle("theme")
    expect(dm.activePanel).toBe("theme")
  })

  it("toggle() closes an already active panel", () => {
    dm.open("theme")
    dm.toggle("theme")
    expect(dm.activePanel).toBeNull()
  })

  it("toggle() switches to a new panel", () => {
    dm.open("sections")
    dm.toggle("page")
    expect(dm.activePanel).toBe("page")
  })

  it("getSnapshot() returns activePanel and isOpen", () => {
    dm.open("theme")
    expect(dm.getSnapshot()).toEqual({ activePanel: "theme", isOpen: true })
  })

  it("subscribe() notifies listeners on state change", () => {
    const fn = vi.fn()
    dm.subscribe(fn)
    dm.open("sections")
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it("subscribe() returns an unsubscribe function", () => {
    const fn = vi.fn()
    const off = dm.subscribe(fn)
    off()
    dm.open("page")
    expect(fn).not.toHaveBeenCalled()
  })

  it("getVersion() increments on every state mutation", () => {
    const v0 = dm.getVersion()
    dm.open("sections")
    expect(dm.getVersion()).toBeGreaterThan(v0)
    dm.close()
    expect(dm.getVersion()).toBeGreaterThan(v0 + 1)
  })

  it("shared drawerManager singleton is exported", () => {
    expect(drawerManager).toBeInstanceOf(DrawerManager)
  })
})
