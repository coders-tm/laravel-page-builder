/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, beforeEach } from "vitest"
import { useStore } from "@/core/store/useStore"

function resetStore() {
  useStore.setState({
    sections: {},
    blocks: {},
    currentPage: {
      sections: {
        hero_1: { type: "hero", settings: {}, blocks: {}, order: [] },
      },
      order: ["hero_1"],
    },
    selectedSection: null,
    selectedBlock: null,
    selectedBlockPath: [],
    pages: [],
    currentSlug: "home",
    loading: false,
    saving: false,
    pageMeta: { meta_title: "", meta_description: "", meta_image: "" },
    themeSettings: { schema: [], values: {} },
  } as any)
}

beforeEach(resetStore)

describe("blockSlice", () => {
  /* ── addBlock ─────────────────────────────────────────────────── */
  it("addBlock() creates block at end of order", () => {
    const id = useStore.getState().addBlock("hero_1", "text", { content: "Hello" })
    const section = useStore.getState().currentPage!.sections["hero_1"]
    expect(section.blocks[id]).toBeDefined()
    expect(section.order).toContain(id)
  })

  it("addBlock() with afterBlockId inserts after it", () => {
    vi.useFakeTimers()
    const id1 = useStore.getState().addBlock("hero_1", "text", {})
    vi.advanceTimersByTime(1)
    const id2 = useStore.getState().addBlock("hero_1", "text", {}, id1)
    vi.useRealTimers()
    const order = useStore.getState().currentPage!.sections["hero_1"].order
    expect(order).toContain(id2)
    const idx1 = order.indexOf(id1)
    const idx2 = order.indexOf(id2)
    expect(idx2).toBeGreaterThan(idx1)
    expect(idx2 - idx1).toBe(1)
  })

  it("addBlock() with parentPath inserts into nested container", () => {
    const containerId = useStore.getState().addBlock("hero_1", "container", {})
    const childId = useStore.getState().addBlock("hero_1", "text", {}, null, [containerId])
    const container = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(container.blocks[childId]).toBeDefined()
    expect(container.order).toContain(childId)
  })

  /* ── removeBlock ──────────────────────────────────────────────── */
  it("removeBlock() removes block and updates order", () => {
    const id = useStore.getState().addBlock("hero_1", "text", {})
    useStore.getState().removeBlock("hero_1", id)
    const section = useStore.getState().currentPage!.sections["hero_1"]
    expect(section.blocks[id]).toBeUndefined()
    expect(section.order).not.toContain(id)
  })

  it("removeBlock() clears selectedBlock if it was the removed block", () => {
    const id = useStore.getState().addBlock("hero_1", "text", {})
    useStore.setState({ selectedBlock: id } as any)
    useStore.getState().removeBlock("hero_1", id)
    expect(useStore.getState().selectedBlock).toBeNull()
  })

  it("removeBlock() works for nested blocks via parentPath", () => {
    const containerId = useStore.getState().addBlock("hero_1", "container", {})
    const childId = useStore.getState().addBlock("hero_1", "text", {}, null, [containerId])
    useStore.getState().removeBlock("hero_1", childId, [containerId])
    const container = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(container.blocks[childId]).toBeUndefined()
  })

  /* ── duplicateBlock ───────────────────────────────────────────── */
  it("duplicateBlock() deep clones block and inserts after original", () => {
    vi.useFakeTimers()
    const id = useStore.getState().addBlock("hero_1", "text", { content: "Orig" })
    vi.advanceTimersByTime(1)
    const newId = useStore.getState().duplicateBlock("hero_1", id)
    vi.useRealTimers()
    const section = useStore.getState().currentPage!.sections["hero_1"]
    expect(section.blocks[newId]).toBeDefined()
    // newId must appear after id in the order
    const idxOrig = section.order.indexOf(id)
    const idxNew = section.order.indexOf(newId)
    expect(idxNew).toBeGreaterThan(idxOrig)
    expect(idxNew - idxOrig).toBe(1)
  })

  it("duplicateBlock() deep clones nested block", () => {
    const containerId = useStore.getState().addBlock("hero_1", "container", {})
    const childId = useStore.getState().addBlock("hero_1", "text", {}, null, [containerId])
    const newId = useStore.getState().duplicateBlock("hero_1", childId, [containerId])
    const container = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(container.blocks[newId]).toBeDefined()
  })

  /* ── reorderBlocks ────────────────────────────────────────────── */
  it("reorderBlocks() replaces order array", () => {
    const id1 = useStore.getState().addBlock("hero_1", "text", {})
    const id2 = useStore.getState().addBlock("hero_1", "text", {})
    useStore.getState().reorderBlocks("hero_1", [id2, id1])
    expect(useStore.getState().currentPage!.sections["hero_1"].order).toEqual([id2, id1])
  })

  it("reorderBlocks() works with parentPath", () => {
    const containerId = useStore.getState().addBlock("hero_1", "container", {})
    const child1 = useStore.getState().addBlock("hero_1", "text", {}, null, [containerId])
    const child2 = useStore.getState().addBlock("hero_1", "text", {}, null, [containerId])
    useStore.getState().reorderBlocks("hero_1", [child2, child1], [containerId])
    const container = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(container.order).toEqual([child2, child1])
  })

  /* ── toggleBlockDisabled ──────────────────────────────────────── */
  it("toggleBlockDisabled() flips disabled flag", () => {
    const id = useStore.getState().addBlock("hero_1", "text", {})
    useStore.getState().toggleBlockDisabled("hero_1", id)
    expect(useStore.getState().currentPage!.sections["hero_1"].blocks[id].disabled).toBe(true)
    useStore.getState().toggleBlockDisabled("hero_1", id)
    expect(useStore.getState().currentPage!.sections["hero_1"].blocks[id].disabled).toBe(false)
  })

  it("toggleBlockDisabled() works for nested blocks", () => {
    const containerId = useStore.getState().addBlock("hero_1", "container", {})
    const childId = useStore.getState().addBlock("hero_1", "text", {}, null, [containerId])
    useStore.getState().toggleBlockDisabled("hero_1", childId, [containerId])
    const container = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(container.blocks[childId].disabled).toBe(true)
  })

  /* ── moveBlock ────────────────────────────────────────────────── */
  it("moveBlock() transfers block to another section at toIndex", () => {
    useStore.setState((s: any) => ({
      currentPage: {
        ...s.currentPage,
        sections: {
          ...s.currentPage.sections,
          hero_2: { type: "hero", settings: {}, blocks: {}, order: [] },
        },
        order: [...s.currentPage.order, "hero_2"],
      },
    }))
    const id = useStore.getState().addBlock("hero_1", "text", {})
    useStore.getState().moveBlock("hero_1", "hero_2", id, [], [], 0)
    expect(useStore.getState().currentPage!.sections["hero_1"].blocks[id]).toBeUndefined()
    expect(useStore.getState().currentPage!.sections["hero_2"].blocks[id]).toBeDefined()
  })

  /* ── renameBlockInstance ──────────────────────────────────────── */
  it("renameBlockInstance() sets _name", () => {
    const id = useStore.getState().addBlock("hero_1", "text", {})
    useStore.getState().renameBlockInstance("hero_1", id, "My Block")
    expect((useStore.getState().currentPage!.sections["hero_1"].blocks[id] as any)._name).toBe(
      "My Block",
    )
  })

  it("renameBlockInstance() clears _name on empty/whitespace", () => {
    const id = useStore.getState().addBlock("hero_1", "text", {})
    useStore.getState().renameBlockInstance("hero_1", id, "Temp")
    useStore.getState().renameBlockInstance("hero_1", id, "   ")
    expect(
      (useStore.getState().currentPage!.sections["hero_1"].blocks[id] as any)._name,
    ).toBeUndefined()
  })

  /* ── updateBlockSettings ──────────────────────────────────────── */
  it("updateBlockSettings() merges patch for top-level block", () => {
    const id = useStore.getState().addBlock("hero_1", "text", { content: "Old" })
    useStore.getState().updateBlockSettings("hero_1", id, { content: "New" })
    expect(useStore.getState().currentPage!.sections["hero_1"].blocks[id].settings.content).toBe(
      "New",
    )
  })

  it("updateBlockSettings() merges patch for nested block", () => {
    const containerId = useStore.getState().addBlock("hero_1", "container", {})
    const childId = useStore
      .getState()
      .addBlock("hero_1", "text", { content: "Old" }, null, [containerId])
    useStore
      .getState()
      .updateBlockSettings("hero_1", childId, { content: "Updated" }, [containerId])
    const container = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(container.blocks[childId].settings.content).toBe("Updated")
  })
})
