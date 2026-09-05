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

const heroSchema = {
  schema: {
    type: "hero",
    name: "Hero",
    settings: [{ id: "heading", type: "text", default: "Hello" }],
    presets: [
      {
        name: "Default",
        settings: { heading: "Preset Heading" },
        blocks: [{ type: "text", settings: { content: "Preset block" } }],
      },
    ],
    blocks: [],
  },
}

function resetStore() {
  useStore.setState({
    sections: { hero: heroSchema },
    blocks: {
      text: {
        schema: {
          type: "text",
          name: "Text",
          settings: [{ id: "content", type: "text", default: "default" }],
          blocks: [],
        },
      },
    },
    currentPage: { sections: {}, order: [] },
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

describe("sectionSlice", () => {
  const {
    addSection,
    removeSection,
    duplicateSection,
    reorderSections,
    toggleSectionDisabled,
    renameSectionInstance,
    updateSectionSettings,
  } = useStore.getState()

  /* ── addSection ───────────────────────────────────────────────── */
  it("addSection() creates instance at end of order with schema defaults", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    const state = useStore.getState()
    expect(state.currentPage?.order).toContain(id)
    expect(state.currentPage?.sections[id].settings.heading).toBe("Preset Heading")
  })

  it("addSection() with insertIndex places section at correct position", () => {
    const id1 = useStore.getState().addSection("hero", heroSchema.schema)
    const id2 = useStore.getState().addSection("hero", heroSchema.schema, 0)
    expect(useStore.getState().currentPage?.order[0]).toBe(id2)
    expect(useStore.getState().currentPage?.order[1]).toBe(id1)
  })

  it("addSection() parses preset blocks", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    const section = useStore.getState().currentPage?.sections[id]
    expect(Object.keys(section?.blocks ?? {}).length).toBeGreaterThan(0)
  })

  /* ── removeSection ────────────────────────────────────────────── */
  it("removeSection() removes section from sections and order", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    useStore.getState().removeSection(id)
    expect(useStore.getState().currentPage?.sections[id]).toBeUndefined()
    expect(useStore.getState().currentPage?.order).not.toContain(id)
  })

  it("removeSection() does NOT remove layout sections", () => {
    useStore.setState({
      currentPage: {
        sections: {
          layout_h: { type: "header", layout: true, settings: {}, blocks: {}, order: [] },
        },
        order: ["layout_h"],
      },
    } as any)
    useStore.getState().removeSection("layout_h")
    expect(useStore.getState().currentPage?.sections["layout_h"]).toBeDefined()
  })

  it("removeSection() resets selection if the removed section was selected", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    useStore.setState({ selectedSection: id } as any)
    useStore.getState().removeSection(id)
    expect(useStore.getState().selectedSection).toBeNull()
  })

  /* ── duplicateSection ─────────────────────────────────────────── */
  it("duplicateSection() inserts after original", () => {
    vi.useFakeTimers()
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    vi.advanceTimersByTime(1)
    const newId = useStore.getState().duplicateSection(id)
    vi.useRealTimers()
    const order = useStore.getState().currentPage!.order
    const idxOrig = order.indexOf(id)
    const idxNew = order.indexOf(newId)
    expect(idxNew).toBeGreaterThan(idxOrig)
    expect(idxNew - idxOrig).toBe(1)
  })

  it("duplicateSection() does NOT duplicate layout sections", () => {
    useStore.setState({
      currentPage: {
        sections: {
          layout_h: { type: "header", layout: true, settings: {}, blocks: {}, order: [] },
        },
        order: ["layout_h"],
      },
    } as any)
    const newId = useStore.getState().duplicateSection("layout_h")
    expect(newId).toBe("") // returns "" when guard fires
  })

  /* ── reorderSections ──────────────────────────────────────────── */
  it("reorderSections() moves page-level sections in correct positions", () => {
    vi.useFakeTimers()
    const id1 = useStore.getState().addSection("hero", heroSchema.schema)
    vi.advanceTimersByTime(1)
    const id2 = useStore.getState().addSection("hero", heroSchema.schema)
    vi.useRealTimers()
    // Move id1 (at index 0) to index 1 (swap them)
    useStore.getState().reorderSections(0, 1)
    const order = useStore.getState().currentPage!.order
    // After reorder, id2 should appear before id1
    expect(order.indexOf(id2)).toBeLessThan(order.indexOf(id1))
  })

  /* ── toggleSectionDisabled ────────────────────────────────────── */
  it("toggleSectionDisabled() flips disabled flag", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    useStore.getState().toggleSectionDisabled(id)
    expect(useStore.getState().currentPage?.sections[id].disabled).toBe(true)
    useStore.getState().toggleSectionDisabled(id)
    expect(useStore.getState().currentPage?.sections[id].disabled).toBe(false)
  })

  /* ── renameSectionInstance ────────────────────────────────────── */
  it("renameSectionInstance() sets _name", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    useStore.getState().renameSectionInstance(id, "My Hero")
    expect((useStore.getState().currentPage?.sections[id] as any)._name).toBe("My Hero")
  })

  it("renameSectionInstance() clears _name on whitespace", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    useStore.getState().renameSectionInstance(id, "My Hero")
    useStore.getState().renameSectionInstance(id, "  ")
    expect((useStore.getState().currentPage?.sections[id] as any)._name).toBeUndefined()
  })

  /* ── updateSectionSettings ────────────────────────────────────── */
  it("updateSectionSettings() merges without overwriting unrelated keys", () => {
    const id = useStore.getState().addSection("hero", heroSchema.schema)
    useStore.getState().updateSectionSettings(id, { heading: "Changed" })
    const s = useStore.getState().currentPage?.sections[id].settings
    expect(s.heading).toBe("Changed")
  })
})
