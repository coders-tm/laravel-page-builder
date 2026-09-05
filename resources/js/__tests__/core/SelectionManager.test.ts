/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { SelectionManager } from "@/core/editor/SelectionManager"
import { EventBus } from "@/core/editor/EventBus"
import { useStore } from "@/core/store/useStore"

// Reset store between tests
beforeEach(() => {
  useStore.setState({
    selectedSection: null,
    selectedBlock: null,
    selectedBlockPath: [],
    currentPage: null,
    pages: [],
    currentSlug: "",
    loading: false,
    saving: false,
    sections: {},
    blocks: {},
    pageMeta: { meta_title: "", meta_description: "", meta_image: "" },
    themeSettings: { schema: [], values: {} },
  } as any)
})

describe("SelectionManager", () => {
  let selection: SelectionManager
  let events: EventBus

  beforeEach(() => {
    events = new EventBus()
    selection = new SelectionManager(events)
  })

  it("selectSection() updates store selectedSection and clears blockPath", () => {
    selection.selectSection("hero_1")
    expect(useStore.getState().selectedSection).toBe("hero_1")
    expect(useStore.getState().selectedBlockPath).toEqual([])
  })

  it("selectBlock() sets section + full block path", () => {
    selection.selectBlock("hero_1", ["block_a", "block_b"])
    expect(useStore.getState().selectedSection).toBe("hero_1")
    expect(useStore.getState().selectedBlock).toBe("block_b")
    expect(useStore.getState().selectedBlockPath).toEqual(["block_a", "block_b"])
  })

  it("clear() resets both section and block", () => {
    selection.selectSection("hero_1")
    selection.clear()
    expect(useStore.getState().selectedSection).toBeNull()
    expect(useStore.getState().selectedBlock).toBeNull()
  })

  it("selection:section-changed is emitted on selectSection()", () => {
    const fn = vi.fn()
    events.on("selection:section-changed", fn)
    selection.selectSection("hero_1")
    expect(fn).toHaveBeenCalledWith({ sectionId: "hero_1" })
  })

  it("selection:cleared emitted when both section and path are null", () => {
    const fn = vi.fn()
    events.on("selection:cleared", fn)
    selection.selectSection("hero_1")
    selection.clear()
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it("selection:block-changed emitted when block path is non-empty", () => {
    const fn = vi.fn()
    events.on("selection:block-changed", fn)
    selection.selectBlock("hero_1", ["block_a"])
    expect(fn).toHaveBeenCalledWith({ sectionId: "hero_1", blockPath: ["block_a"] })
  })

  it("selecting the same section+block twice does not re-emit", () => {
    const fn = vi.fn()
    events.on("selection:section-changed", fn)
    selection.selectSection("hero_1")
    selection.selectSection("hero_1") // same — should be idempotent
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it("syncFromExternal() does NOT call the adapter", () => {
    const adapter = { setSelection: vi.fn(), clearSelection: vi.fn() }
    selection.setAdapter(adapter)
    selection.syncFromExternal("hero_1", ["block_a"])
    expect(adapter.setSelection).not.toHaveBeenCalled()
    expect(adapter.clearSelection).not.toHaveBeenCalled()
  })

  it("selectSection() calls adapter.setSelection() when adapter is set", () => {
    const adapter = { setSelection: vi.fn() }
    selection.setAdapter(adapter)
    selection.selectSection("hero_1")
    expect(adapter.setSelection).toHaveBeenCalledWith("hero_1", [])
  })

  it("clear() calls adapter.clearSelection() when adapter provides it", () => {
    const adapter = { setSelection: vi.fn(), clearSelection: vi.fn() }
    selection.setAdapter(adapter)
    selection.selectSection("hero_1")
    selection.clear()
    expect(adapter.clearSelection).toHaveBeenCalledTimes(1)
  })

  it("getSectionId() reads from store", () => {
    selection.selectSection("hero_1")
    expect(selection.getSectionId()).toBe("hero_1")
  })

  it("getBlockId() reads from store", () => {
    selection.selectBlock("hero_1", ["block_a"])
    expect(selection.getBlockId()).toBe("block_a")
  })

  it("getBlockPath() reads from store", () => {
    selection.selectBlock("hero_1", ["block_a", "block_b"])
    expect(selection.getBlockPath()).toEqual(["block_a", "block_b"])
  })
})
