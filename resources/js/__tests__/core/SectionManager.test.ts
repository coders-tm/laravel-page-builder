/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { SectionManager } from "@/core/editor/SectionManager"
import { EventBus } from "@/core/editor/EventBus"
import { useStore } from "@/core/store/useStore"

const heroSchema = {
  type: "hero",
  name: "Hero",
  settings: [
    { id: "heading", type: "text", default: "Hello" },
    { id: "subheading", type: "text", default: "World" },
  ],
  presets: [
    {
      name: "Default",
      settings: { heading: "Welcome" },
    },
  ],
}

function makeManager() {
  const events = new EventBus()
  const manager = new SectionManager(events)
  return { events, manager }
}

function resetStore() {
  useStore.setState({
    sections: {},
    blocks: {},
    currentPage: { sections: {}, order: [] },
    selectedSection: null,
    selectedBlock: null,
    selectedBlockPath: [],
  } as any)
}

beforeEach(resetStore)

describe("SectionManager", () => {
  /* ── register / unregister ────────────────────────────────────── */
  it("register() inserts schema and emits section:registered", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:registered", fn)
    manager.register("hero", heroSchema)
    expect(manager.getSchema("hero")).toEqual(heroSchema)
    expect(fn).toHaveBeenCalledWith({ type: "hero" })
  })

  it("unregister() removes schema and emits section:unregistered", () => {
    const { events, manager } = makeManager()
    manager.register("hero", heroSchema)
    const fn = vi.fn()
    events.on("section:unregistered", fn)
    manager.unregister("hero")
    expect(manager.getSchema("hero")).toBeNull()
    expect(fn).toHaveBeenCalledWith({ type: "hero" })
  })

  it("hasSchema() returns false for unknown type", () => {
    const { manager } = makeManager()
    expect(manager.hasSchema("missing")).toBe(false)
  })

  /* ── add ──────────────────────────────────────────────────────── */
  it("add() creates a section with schema defaults", () => {
    const { events, manager } = makeManager()
    const sectionId = manager.add("hero", heroSchema)
    const instance = manager.getInstance(sectionId)
    expect(instance).not.toBeNull()
    expect(instance?.settings.heading).toBe("Welcome") // preset overrides default
    expect(instance?.settings.subheading).toBe("World") // default kept
  })

  it("add() returns the new section ID", () => {
    const { manager } = makeManager()
    const id = manager.add("hero", heroSchema)
    expect(id).toMatch(/^hero_/)
  })

  it("add() emits section:added with sectionId and type", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:added", fn)
    const id = manager.add("hero", heroSchema)
    expect(fn).toHaveBeenCalledWith({ sectionId: id, type: "hero" })
  })

  it("add() with insertIndex inserts at the correct position", () => {
    const { manager } = makeManager()
    manager.add("hero", heroSchema) // index 0
    const id2 = manager.add("hero", heroSchema, 0) // insert at 0
    const order = manager.getOrder()
    expect(order[0]).toBe(id2)
  })

  /* ── remove ───────────────────────────────────────────────────── */
  it("remove() deletes the section and emits section:removed", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:removed", fn)
    const id = manager.add("hero", heroSchema)
    manager.remove(id)
    expect(manager.getInstance(id)).toBeNull()
    expect(fn).toHaveBeenCalledWith({ sectionId: id })
  })

  it("remove() does not remove layout sections", () => {
    useStore.setState((s: any) => ({
      currentPage: {
        sections: {
          layout_header: { type: "header", layout: true, settings: {}, blocks: {}, order: [] },
        },
        order: ["layout_header"],
      },
    }))
    const { manager } = makeManager()
    manager.remove("layout_header")
    expect(manager.getInstance("layout_header")).not.toBeNull()
  })

  /* ── duplicate ────────────────────────────────────────────────── */
  it("duplicate() creates a copy inserted after original", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:duplicated", fn)
    vi.useFakeTimers()
    const id = manager.add("hero", heroSchema)
    vi.advanceTimersByTime(1)
    const newId = manager.duplicate(id)
    vi.useRealTimers()
    const order = manager.getOrder()
    // newId must appear directly after id
    const idxOrig = order.indexOf(id)
    const idxNew = order.indexOf(newId)
    expect(idxNew).toBeGreaterThan(idxOrig)
    expect(idxNew - idxOrig).toBe(1)
    expect(fn).toHaveBeenCalledWith({ sectionId: id, newId })
  })

  /* ── reorder ──────────────────────────────────────────────────── */
  it("reorder() moves section and emits section:reordered", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:reordered", fn)
    const id0 = manager.add("hero", heroSchema)
    const id1 = manager.add("hero", heroSchema)
    manager.reorder(0, 1)
    const order = manager.getPageOrder()
    expect(order[0]).toBe(id1)
    expect(fn).toHaveBeenCalled()
  })

  /* ── updateSettings ───────────────────────────────────────────── */
  it("updateSettings() merges patch and emits section:settings-changed", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:settings-changed", fn)
    const id = manager.add("hero", heroSchema)
    manager.updateSettings(id, { heading: "Updated" })
    expect(manager.getInstance(id)?.settings.heading).toBe("Updated")
    expect(fn).toHaveBeenCalledWith({ sectionId: id, values: { heading: "Updated" } })
  })

  it("updateSettings() does not overwrite unrelated settings", () => {
    const { manager } = makeManager()
    const id = manager.add("hero", heroSchema)
    manager.updateSettings(id, { heading: "New" })
    expect(manager.getInstance(id)?.settings.subheading).toBe("World")
  })

  /* ── toggleDisabled ───────────────────────────────────────────── */
  it("toggleDisabled() flips disabled and emits section:toggled", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:toggled", fn)
    const id = manager.add("hero", heroSchema)
    manager.toggleDisabled(id)
    expect(manager.getInstance(id)?.disabled).toBe(true)
    expect(fn).toHaveBeenCalledWith({ sectionId: id, disabled: true })
    manager.toggleDisabled(id)
    expect(manager.getInstance(id)?.disabled).toBe(false)
  })

  /* ── rename ───────────────────────────────────────────────────── */
  it("rename() sets _name and emits section:renamed", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("section:renamed", fn)
    const id = manager.add("hero", heroSchema)
    manager.rename(id, "My Hero")
    expect((manager.getInstance(id) as any)?._name).toBe("My Hero")
    expect(fn).toHaveBeenCalledWith({ sectionId: id, name: "My Hero" })
  })

  it("rename() clears _name on empty/whitespace string", () => {
    const { manager } = makeManager()
    const id = manager.add("hero", heroSchema)
    manager.rename(id, "My Hero")
    manager.rename(id, "  ")
    expect((manager.getInstance(id) as any)?._name).toBeUndefined()
  })

  /* ── queries ──────────────────────────────────────────────────── */
  it("getInstances() returns all current page sections", () => {
    const { manager } = makeManager()
    manager.add("hero", heroSchema)
    expect(Object.keys(manager.getInstances()).length).toBeGreaterThan(0)
  })

  it("getPageOrder() excludes layout sections", () => {
    const { manager } = makeManager()
    useStore.setState((s: any) => ({
      currentPage: {
        sections: {
          layout_header: { type: "header", layout: true, settings: {}, blocks: {}, order: [] },
        },
        order: ["layout_header"],
      },
    }))
    manager.add("hero", heroSchema)
    const pageOrder = manager.getPageOrder()
    expect(pageOrder.every((id) => !id.startsWith("layout_"))).toBe(true)
  })
})
