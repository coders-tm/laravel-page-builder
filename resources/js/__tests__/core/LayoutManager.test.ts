/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { LayoutManager } from "@/core/editor/LayoutManager"
import { EventBus } from "@/core/editor/EventBus"
import { useStore } from "@/core/store/useStore"

beforeEach(() => {
  useStore.setState({
    currentPage: { sections: {}, order: [] },
  } as any)
})

describe("LayoutManager", () => {
  let layout: LayoutManager
  let events: EventBus

  beforeEach(() => {
    events = new EventBus()
    layout = new LayoutManager(events)
  })

  /* ── sidebarTab ────────────────────────────────────────────────── */
  it("default sidebarTab is 'sections'", () => {
    expect(layout.sidebarTab).toBe("sections")
  })

  it("setSidebarTab() changes tab and emits layout:sidebar-tab-changed", () => {
    const fn = vi.fn()
    events.on("layout:sidebar-tab-changed", fn)
    layout.setSidebarTab("page")
    expect(layout.sidebarTab).toBe("page")
    expect(fn).toHaveBeenCalledWith({ tab: "page" })
  })

  it("setSidebarTab() is a no-op when tab is already active", () => {
    const fn = vi.fn()
    events.on("layout:sidebar-tab-changed", fn)
    layout.setSidebarTab("sections")
    expect(fn).not.toHaveBeenCalled()
  })

  /* ── inspector ────────────────────────────────────────────────── */
  it("inspectorEnabled defaults to true", () => {
    expect(layout.inspectorEnabled).toBe(true)
  })

  it("setInspectorEnabled() changes flag and emits layout:inspector-toggled", () => {
    const fn = vi.fn()
    events.on("layout:inspector-toggled", fn)
    layout.setInspectorEnabled(false)
    expect(layout.inspectorEnabled).toBe(false)
    expect(fn).toHaveBeenCalledWith({ enabled: false })
  })

  it("setInspectorEnabled() is no-op when value unchanged", () => {
    const fn = vi.fn()
    events.on("layout:inspector-toggled", fn)
    layout.setInspectorEnabled(true) // already true
    expect(fn).not.toHaveBeenCalled()
  })

  it("toggleInspector() flips the flag", () => {
    layout.toggleInspector()
    expect(layout.inspectorEnabled).toBe(false)
    layout.toggleInspector()
    expect(layout.inspectorEnabled).toBe(true)
  })

  /* ── device ───────────────────────────────────────────────────── */
  it("device defaults to 'desktop'", () => {
    expect(layout.device).toBe("desktop")
  })

  it("setDevice() changes device and emits layout:device-changed", () => {
    const fn = vi.fn()
    events.on("layout:device-changed", fn)
    layout.setDevice("mobile")
    expect(layout.device).toBe("mobile")
    expect(fn).toHaveBeenCalledWith({ device: "mobile" })
  })

  it("setDevice() is no-op when device is unchanged", () => {
    const fn = vi.fn()
    events.on("layout:device-changed", fn)
    layout.setDevice("desktop")
    expect(fn).not.toHaveBeenCalled()
  })

  it("isFullscreen is true only when device is 'fullscreen'", () => {
    expect(layout.isFullscreen).toBe(false)
    layout.setDevice("fullscreen")
    expect(layout.isFullscreen).toBe(true)
  })

  /* ── isDualSidebar ────────────────────────────────────────────── */
  it("isDualSidebar is true when viewportWidth >= 1549 and not fullscreen", () => {
    layout.setViewportWidth(1549)
    expect(layout.isDualSidebar).toBe(true)
  })

  it("isDualSidebar is false when fullscreen even if wide", () => {
    layout.setViewportWidth(2000)
    layout.setDevice("fullscreen")
    expect(layout.isDualSidebar).toBe(false)
  })

  it("isDualSidebar is false below 1549px", () => {
    layout.setViewportWidth(1200)
    expect(layout.isDualSidebar).toBe(false)
  })

  /* ── setViewportWidth ─────────────────────────────────────────── */
  it("setViewportWidth() floors the value", () => {
    layout.setViewportWidth(1600.9)
    expect(layout.getSnapshot().isDualSidebar).toBe(true) // 1600 >= 1549
  })

  it("setViewportWidth() ignores non-finite values", () => {
    expect(() => layout.setViewportWidth(NaN)).not.toThrow()
    expect(() => layout.setViewportWidth(Infinity)).not.toThrow()
  })

  /* ── drag ─────────────────────────────────────────────────────── */
  it("startDrag() sets isDraggingLayout to true", () => {
    layout.startDrag()
    expect(layout.isDraggingLayout).toBe(true)
  })

  it("endDrag() sets isDraggingLayout to false", () => {
    layout.startDrag()
    layout.endDrag()
    expect(layout.isDraggingLayout).toBe(false)
  })

  it("startDrag() is a no-op when already dragging", () => {
    layout.startDrag()
    const v = layout.getVersion()
    layout.startDrag()
    expect(layout.getVersion()).toBe(v) // no state change = no version bump
  })

  /* ── addSectionModal ──────────────────────────────────────────── */
  it("openAddSectionModal() sets isOpen true with null insertIndex when no target", () => {
    layout.openAddSectionModal()
    expect(layout.addSectionModal.isOpen).toBe(true)
    expect(layout.addSectionModal.insertIndex).toBeNull()
  })

  it("openAddSectionModal() computes insertIndex for 'after' position", () => {
    useStore.setState({
      currentPage: {
        order: ["sec_a", "sec_b", "sec_c"],
        sections: {},
      },
    } as any)
    layout.openAddSectionModal("after", "sec_a")
    expect(layout.addSectionModal.insertIndex).toBe(1)
  })

  it("openAddSectionModal() computes insertIndex for 'before' position", () => {
    useStore.setState({
      currentPage: {
        order: ["sec_a", "sec_b"],
        sections: {},
      },
    } as any)
    layout.openAddSectionModal("before", "sec_b")
    expect(layout.addSectionModal.insertIndex).toBe(1)
  })

  it("closeAddSectionModal() sets isOpen false", () => {
    layout.openAddSectionModal()
    layout.closeAddSectionModal()
    expect(layout.addSectionModal.isOpen).toBe(false)
  })

  it("closeAddSectionModal() is no-op when already closed", () => {
    const v = layout.getVersion()
    layout.closeAddSectionModal()
    expect(layout.getVersion()).toBe(v)
  })

  /* ── addBlockModal ────────────────────────────────────────────── */
  it("openAddBlockModal() sets isOpen true with block types", () => {
    const types = [{ type: "text", name: "Text" } as any]
    layout.openAddBlockModal(types, "hero_1")
    expect(layout.addBlockModal.isOpen).toBe(true)
    expect(layout.addBlockModal.blockTypes).toEqual(types)
    expect(layout.addBlockModal.sectionId).toBe("hero_1")
  })

  it("openAddBlockModal() is no-op when blockTypes list is empty", () => {
    layout.openAddBlockModal([], "hero_1")
    expect(layout.addBlockModal.isOpen).toBe(false)
  })

  it("closeAddBlockModal() resets modal state", () => {
    const types = [{ type: "text", name: "Text" } as any]
    layout.openAddBlockModal(types, "hero_1")
    layout.closeAddBlockModal()
    expect(layout.addBlockModal.isOpen).toBe(false)
    expect(layout.addBlockModal.sectionId).toBeNull()
  })

  it("closeAddBlockModal() is no-op when already closed", () => {
    const v = layout.getVersion()
    layout.closeAddBlockModal()
    expect(layout.getVersion()).toBe(v)
  })

  /* ── subscribe / version ──────────────────────────────────────── */
  it("subscribe() notifies listeners on every state change", () => {
    const fn = vi.fn()
    layout.subscribe(fn)
    layout.setSidebarTab("theme")
    expect(fn).toHaveBeenCalled()
  })

  it("subscribe() returns an unsubscribe function", () => {
    const fn = vi.fn()
    const off = layout.subscribe(fn)
    off()
    layout.setSidebarTab("page")
    expect(fn).not.toHaveBeenCalled()
  })
})
