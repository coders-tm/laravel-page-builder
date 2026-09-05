/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { BlockManager } from "@/core/editor/BlockManager"
import { EventBus } from "@/core/editor/EventBus"
import { useStore } from "@/core/store/useStore"

const textBlockSchema = {
  type: "text",
  name: "Text",
  settings: [{ id: "content", type: "text", default: "Default text" }],
  blocks: [],
}

const containerBlockSchema = {
  type: "container",
  name: "Container",
  settings: [],
  blocks: [{ type: "@theme" }],
}

function makeManager() {
  const events = new EventBus()
  const manager = new BlockManager(events)
  return { events, manager }
}

function resetStore(withSection = true) {
  useStore.setState({
    sections: { text: { schema: textBlockSchema }, container: { schema: containerBlockSchema } },
    blocks: { text: { schema: textBlockSchema }, container: { schema: containerBlockSchema } },
    currentPage: withSection
      ? {
          sections: {
            hero_1: {
              type: "hero",
              settings: {},
              blocks: {},
              order: [],
            },
          },
          order: ["hero_1"],
        }
      : { sections: {}, order: [] },
    selectedSection: null,
    selectedBlock: null,
    selectedBlockPath: [],
  } as any)
}

beforeEach(resetStore)

describe("BlockManager", () => {
  /* ── register / unregister ────────────────────────────────────── */
  it("register() adds block schema and emits block:registered", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:registered", fn)
    manager.register("image", { type: "image", name: "Image", settings: [], blocks: [] })
    expect(manager.hasSchema("image")).toBe(true)
    expect(fn).toHaveBeenCalledWith({ type: "image" })
  })

  it("unregister() removes block schema and emits block:unregistered", () => {
    const { events, manager } = makeManager()
    manager.register("image", { type: "image" } as any)
    const fn = vi.fn()
    events.on("block:unregistered", fn)
    manager.unregister("image")
    expect(manager.hasSchema("image")).toBe(false)
    expect(fn).toHaveBeenCalledWith({ type: "image" })
  })

  it("getSchema() returns null for unknown type", () => {
    const { manager } = makeManager()
    expect(manager.getSchema("unknown")).toBeNull()
  })

  /* ── add ──────────────────────────────────────────────────────── */
  it("add() creates a block at the end of section order", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:added", fn)
    const id = manager.add("hero_1", "text", { content: "Hello" })
    const section = useStore.getState().currentPage!.sections["hero_1"]
    expect(section.blocks[id]).toBeDefined()
    expect(section.order).toContain(id)
    expect(fn).toHaveBeenCalledWith(
      expect.objectContaining({ sectionId: "hero_1", blockId: id, type: "text" }),
    )
  })

  it("add() with afterBlockId inserts after the specified block", () => {
    const { manager } = makeManager()
    vi.useFakeTimers()
    const id1 = manager.add("hero_1", "text", {})
    vi.advanceTimersByTime(1)
    const id2 = manager.add("hero_1", "text", {}, id1)
    vi.useRealTimers()
    const order = useStore.getState().currentPage!.sections["hero_1"].order
    expect(order).toContain(id2)
    const idx1 = order.indexOf(id1)
    const idx2 = order.indexOf(id2)
    expect(idx2).toBeGreaterThan(idx1)
    expect(idx2 - idx1).toBe(1)
  })

  it("add() with parentPath inserts into nested container", () => {
    const { manager } = makeManager()
    // Add a container block first
    const containerId = manager.add("hero_1", "container", {})
    // Add a child text block inside the container
    const childId = manager.add("hero_1", "text", { content: "Child" }, null, [containerId])
    const containerBlock = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(containerBlock.blocks[childId]).toBeDefined()
    expect(containerBlock.order).toContain(childId)
  })

  /* ── remove ───────────────────────────────────────────────────── */
  it("remove() deletes block and removes from order, emits block:removed", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:removed", fn)
    const id = manager.add("hero_1", "text", {})
    manager.remove("hero_1", id)
    const section = useStore.getState().currentPage!.sections["hero_1"]
    expect(section.blocks[id]).toBeUndefined()
    expect(section.order).not.toContain(id)
    expect(fn).toHaveBeenCalledWith(expect.objectContaining({ sectionId: "hero_1", blockId: id }))
  })

  it("remove() works for nested blocks via parentPath", () => {
    const { manager } = makeManager()
    const containerId = manager.add("hero_1", "container", {})
    const childId = manager.add("hero_1", "text", {}, null, [containerId])
    manager.remove("hero_1", childId, [containerId])
    const containerBlock = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(containerBlock.blocks[childId]).toBeUndefined()
  })

  /* ── duplicate ────────────────────────────────────────────────── */
  it("duplicate() deep-clones block after original and emits block:duplicated", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:duplicated", fn)
    vi.useFakeTimers()
    const id = manager.add("hero_1", "text", { content: "Orig" })
    vi.advanceTimersByTime(1)
    const newId = manager.duplicate("hero_1", id)
    vi.useRealTimers()
    const section = useStore.getState().currentPage!.sections["hero_1"]
    expect(section.blocks[newId]).toBeDefined()
    // newId must appear directly after original
    const idxOrig = section.order.indexOf(id)
    const idxNew = section.order.indexOf(newId)
    expect(idxNew).toBeGreaterThan(idxOrig)
    expect(idxNew - idxOrig).toBe(1)
    expect(fn).toHaveBeenCalledWith(
      expect.objectContaining({ sectionId: "hero_1", blockId: id, newId }),
    )
  })

  it("duplicate() works for nested blocks", () => {
    const { manager } = makeManager()
    const containerId = manager.add("hero_1", "container", {})
    const childId = manager.add("hero_1", "text", {}, null, [containerId])
    const newId = manager.duplicate("hero_1", childId, [containerId])
    const containerBlock = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(containerBlock.blocks[newId]).toBeDefined()
  })

  /* ── reorder ──────────────────────────────────────────────────── */
  it("reorder() replaces order array and emits block:reordered", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:reordered", fn)
    const id1 = manager.add("hero_1", "text", {})
    const id2 = manager.add("hero_1", "text", {})
    manager.reorder("hero_1", [id2, id1])
    const order = useStore.getState().currentPage!.sections["hero_1"].order
    expect(order).toEqual([id2, id1])
    expect(fn).toHaveBeenCalledWith(expect.objectContaining({ order: [id2, id1] }))
  })

  it("reorder() works for nested blocks with parentPath", () => {
    const { manager } = makeManager()
    const containerId = manager.add("hero_1", "container", {})
    const child1 = manager.add("hero_1", "text", {}, null, [containerId])
    const child2 = manager.add("hero_1", "text", {}, null, [containerId])
    manager.reorder("hero_1", [child2, child1], [containerId])
    const containerBlock = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(containerBlock.order).toEqual([child2, child1])
  })

  /* ── updateSettings ───────────────────────────────────────────── */
  it("updateSettings() merges patch and emits block:settings-changed", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:settings-changed", fn)
    const id = manager.add("hero_1", "text", { content: "Old" })
    manager.updateSettings("hero_1", id, { content: "New" })
    const block = useStore.getState().currentPage!.sections["hero_1"].blocks[id]
    expect(block.settings.content).toBe("New")
    expect(fn).toHaveBeenCalledWith(expect.objectContaining({ sectionId: "hero_1", blockId: id }))
  })

  it("updateSettings() works for nested blocks via parentPath", () => {
    const { manager } = makeManager()
    const containerId = manager.add("hero_1", "container", {})
    const childId = manager.add("hero_1", "text", { content: "Old" }, null, [containerId])
    manager.updateSettings("hero_1", childId, { content: "Updated" }, [containerId])
    const containerBlock = useStore.getState().currentPage!.sections["hero_1"].blocks[containerId]
    expect(containerBlock.blocks[childId].settings.content).toBe("Updated")
  })

  /* ── toggleDisabled ───────────────────────────────────────────── */
  it("toggleDisabled() flips disabled and emits block:toggled", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:toggled", fn)
    const id = manager.add("hero_1", "text", {})
    manager.toggleDisabled("hero_1", id)
    const block = useStore.getState().currentPage!.sections["hero_1"].blocks[id]
    expect(block.disabled).toBe(true)
    expect(fn).toHaveBeenCalledWith(
      expect.objectContaining({ sectionId: "hero_1", blockId: id, disabled: true }),
    )
    manager.toggleDisabled("hero_1", id)
    expect(useStore.getState().currentPage!.sections["hero_1"].blocks[id].disabled).toBe(false)
  })

  /* ── move ─────────────────────────────────────────────────────── */
  it("move() transfers a block to another section and emits block:moved", () => {
    const { events, manager } = makeManager()
    // Add second section
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
    const fn = vi.fn()
    events.on("block:moved", fn)
    const id = manager.add("hero_1", "text", {})
    manager.move("hero_1", "hero_2", id, [], [], 0)
    const sec1 = useStore.getState().currentPage!.sections["hero_1"]
    const sec2 = useStore.getState().currentPage!.sections["hero_2"]
    expect(sec1.blocks[id]).toBeUndefined()
    expect(sec2.blocks[id]).toBeDefined()
    expect(fn).toHaveBeenCalledWith(
      expect.objectContaining({ fromSectionId: "hero_1", toSectionId: "hero_2" }),
    )
  })

  /* ── rename ───────────────────────────────────────────────────── */
  it("rename() sets _name and emits block:renamed", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("block:renamed", fn)
    const id = manager.add("hero_1", "text", {})
    manager.rename("hero_1", id, "My Block")
    const block = useStore.getState().currentPage!.sections["hero_1"].blocks[id]
    expect((block as any)._name).toBe("My Block")
    expect(fn).toHaveBeenCalled()
  })

  it("rename() clears _name on empty string", () => {
    const { manager } = makeManager()
    const id = manager.add("hero_1", "text", {})
    manager.rename("hero_1", id, "Temp")
    manager.rename("hero_1", id, "  ")
    const block = useStore.getState().currentPage!.sections["hero_1"].blocks[id]
    expect((block as any)._name).toBeUndefined()
  })

  /* ── getAddableTypes ──────────────────────────────────────────── */
  it("getAddableTypes() returns all theme blocks when section uses @theme", () => {
    const { manager } = makeManager()
    // Set section schema with @theme
    useStore.setState((s: any) => ({
      sections: {
        hero: {
          schema: { type: "hero", name: "Hero", settings: [], blocks: [{ type: "@theme" }] },
        },
      },
      currentPage: {
        sections: { hero_1: { type: "hero", settings: {}, blocks: {}, order: [] } },
        order: ["hero_1"],
      },
    }))
    const types = manager.getAddableTypes("hero_1")
    expect(types.length).toBeGreaterThan(0)
  })

  it("getAddableTypes() returns empty for section with no blocks", () => {
    const { manager } = makeManager()
    useStore.setState((s: any) => ({
      sections: { hero: { schema: { type: "hero", name: "Hero", settings: [], blocks: [] } } },
      currentPage: {
        sections: { hero_1: { type: "hero", settings: {}, blocks: {}, order: [] } },
        order: ["hero_1"],
      },
    }))
    const types = manager.getAddableTypes("hero_1")
    expect(types).toEqual([])
  })

  /* ── getInstance ──────────────────────────────────────────────── */
  it("getInstance() returns null for empty path", () => {
    const { manager } = makeManager()
    expect(manager.getInstance("hero_1", [])).toBeNull()
  })

  it("getInstance() returns nested block", () => {
    const { manager } = makeManager()
    const containerId = manager.add("hero_1", "container", {})
    const childId = manager.add("hero_1", "text", {}, null, [containerId])
    const block = manager.getInstance("hero_1", [containerId, childId])
    expect(block).toBeDefined()
    expect(block?.type).toBe("text")
  })
})
