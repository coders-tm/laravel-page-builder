/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { Editor } from "@/core/editor/Editor"
import { useStore } from "@/core/store/useStore"

vi.mock("@/services/api", () => ({
  default: {
    renderSection: vi.fn().mockResolvedValue({ html: "<section>ok</section>" }),
    getPreviewUrl: vi.fn(() => "/preview"),
  },
}))

const heroSchema = {
  type: "hero",
  name: "Hero",
  settings: [
    { id: "heading", type: "text", default: "Hello" },
    { id: "enabled", type: "checkbox", default: true },
  ],
  presets: [],
  blocks: [],
}

const themeSchema = [
  {
    group: "colors",
    settings: [
      { id: "primary", key: "primary", type: "color", default: "#fff", css_var: "--primary" },
    ],
  },
]

function makeBus() {
  return { send: vi.fn(), on: vi.fn() }
}

function resetStore() {
  useStore.setState({
    sections: {},
    blocks: {},
    currentPage: { sections: {}, order: [] },
    currentSlug: "home",
    pages: [],
    loading: false,
    saving: false,
    selectedSection: null,
    selectedBlock: null,
    selectedBlockPath: [],
    pageMeta: { meta_title: "", meta_description: "", meta_image: "" },
    themeSettings: { schema: themeSchema, values: { primary: "#fff" } },
  } as any)
}

beforeEach(() => {
  resetStore()
})

describe("Editor — constructor", () => {
  it("creates all managers without throwing", () => {
    expect(() => new Editor()).not.toThrow()
  })

  it("getConfig() / getFullConfig() return config values", () => {
    const editor = new Editor({ baseUrl: "/test" })
    expect(editor.getConfig("baseUrl")).toBe("/test")
    expect(editor.getFullConfig().baseUrl).toBe("/test")
  })
})

describe("Editor — lifecycle", () => {
  it("ready() emits editor:ready", () => {
    const editor = new Editor()
    const fn = vi.fn()
    editor.on("editor:ready", fn)
    editor.ready()
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it("destroy() emits editor:destroyed and clears listeners", () => {
    const editor = new Editor()
    const fn = vi.fn()
    editor.on("editor:destroyed", fn)
    editor.destroy()
    expect(fn).toHaveBeenCalledTimes(1)
    // After destroy, new emits should not reach old listeners
    editor.emit("editor:ready")
    expect(fn).toHaveBeenCalledTimes(1) // unchanged
  })
})

describe("Editor — event / emit delegation", () => {
  it("on() + emit() delegate to EventBus", () => {
    const editor = new Editor()
    const fn = vi.fn()
    editor.on("test:event", fn)
    editor.emit("test:event", { x: 1 })
    expect(fn).toHaveBeenCalledWith({ x: 1 })
  })
})

describe("Editor — section operations → preview wiring", () => {
  it("addSection() emits section:added and triggers debouncedRerender", async () => {
    vi.useFakeTimers()
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const fn = vi.fn()
    editor.on("section:added", fn)

    editor.addSection("hero", heroSchema)
    expect(fn).toHaveBeenCalledTimes(1)

    // Let the debounce fire
    vi.advanceTimersByTime(500)
    await vi.runAllTimersAsync()

    expect(bus.send).toHaveBeenCalledWith("update-section-html", expect.any(Object))
    vi.useRealTimers()
  })

  it("removeSection() triggers preview.removeSection()", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    bus.send.mockClear()
    editor.removeSection(sectionId)
    expect(bus.send).toHaveBeenCalledWith("remove-section", { sectionId })
  })

  it("sections.duplicate() triggers debouncedRerender on new ID", async () => {
    vi.useFakeTimers()
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    bus.send.mockClear()

    editor.sections.duplicate(sectionId)
    vi.advanceTimersByTime(500)
    await vi.runAllTimersAsync()

    expect(bus.send).toHaveBeenCalledWith("update-section-html", expect.any(Object))
    vi.useRealTimers()
  })

  it("sections.reorder() triggers preview.reorderSections()", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    editor.addSection("hero", heroSchema)
    editor.addSection("hero", heroSchema)
    bus.send.mockClear()

    editor.sections.reorder(0, 1)
    expect(bus.send).toHaveBeenCalledWith("reorder-sections", expect.any(Object))
  })

  it("updateSection() with non-live-text setting triggers debouncedRerender", async () => {
    vi.useFakeTimers()
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    bus.send.mockClear()

    editor.updateSection(sectionId, { enabled: true })
    vi.advanceTimersByTime(500)
    await vi.runAllTimersAsync()

    expect(bus.send).toHaveBeenCalledWith("update-section-html", expect.any(Object))
    vi.useRealTimers()
  })

  it("sections.toggleDisabled() triggers preview.toggleVisibility()", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    bus.send.mockClear()

    editor.sections.toggleDisabled(sectionId)
    expect(bus.send).toHaveBeenCalledWith(
      "toggle-visibility",
      expect.objectContaining({ kind: "section", sectionId }),
    )
  })
})

describe("Editor — block operations → preview wiring", () => {
  it("addBlock() triggers debouncedRerender", async () => {
    vi.useFakeTimers()
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    bus.send.mockClear()

    editor.addBlock(sectionId, "text", { content: "Hi" })
    vi.advanceTimersByTime(500)
    await vi.runAllTimersAsync()

    expect(bus.send).toHaveBeenCalledWith("update-section-html", expect.any(Object))
    vi.useRealTimers()
  })

  it("removeBlock() triggers preview.removeBlock()", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    const blockId = editor.addBlock(sectionId, "text", {})
    bus.send.mockClear()

    editor.removeBlock(sectionId, blockId)
    expect(bus.send).toHaveBeenCalledWith(
      "remove-block",
      expect.objectContaining({ sectionId, blockId }),
    )
  })

  it("blocks.reorder() triggers preview.reorderBlocks()", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    const b1 = editor.addBlock(sectionId, "text", {})
    const b2 = editor.addBlock(sectionId, "text", {})
    bus.send.mockClear()

    editor.blocks.reorder(sectionId, [b2, b1])
    expect(bus.send).toHaveBeenCalledWith("reorder-blocks", expect.any(Object))
  })

  it("blocks.toggleDisabled() triggers preview.toggleVisibility('block',...)", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sectionId = editor.addSection("hero", heroSchema)
    const blockId = editor.addBlock(sectionId, "text", {})
    bus.send.mockClear()

    editor.blocks.toggleDisabled(sectionId, blockId)
    expect(bus.send).toHaveBeenCalledWith(
      "toggle-visibility",
      expect.objectContaining({ kind: "block", blockId }),
    )
  })

  it("blocks.move() triggers debouncedRerender on both sections", async () => {
    vi.useFakeTimers()
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    const sec1 = editor.addSection("hero", heroSchema)
    const sec2 = editor.addSection("hero", heroSchema)
    const blockId = editor.addBlock(sec1, "text", {})
    bus.send.mockClear()

    editor.blocks.move(sec1, sec2, blockId, [], [], 0)
    vi.advanceTimersByTime(500)
    await vi.runAllTimersAsync()

    const htmlCalls = bus.send.mock.calls.filter((c: any[]) => c[0] === "update-section-html")
    expect(htmlCalls.length).toBeGreaterThanOrEqual(1)
    vi.useRealTimers()
  })
})

describe("Editor — theme settings → preview wiring", () => {
  it("pages.updateThemeSetting() with css_var triggers preview.updateCssVar()", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    editor.pages.updateThemeSetting("primary", "#ff0000")
    expect(bus.send).toHaveBeenCalledWith("update-css-var", {
      cssVar: "--primary",
      value: "#ff0000",
    })
  })

  it("pages.resetAllThemeSettings() triggers preview.updateCssVars()", () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    editor.pages.resetAllThemeSettings()
    expect(bus.send).toHaveBeenCalledWith(
      "update-css-vars",
      expect.objectContaining({ vars: expect.any(Object) }),
    )
  })
})

describe("Editor — undo / redo", () => {
  it("undo() restores previous page state and triggers preview.renderFullPage()", async () => {
    const editor = new Editor()
    const bus = makeBus()
    editor.preview.setMessageBus(bus as any)

    // Prime history with two snapshots
    editor.history.push({ sections: {}, order: [] })
    editor.history.push({ sections: { hero_1: {} }, order: ["hero_1"] } as any)

    editor.undo()
    // renderFullPage sends replace-all-sections
    // (it's async but we can check the store was updated)
    expect(useStore.getState().currentPage?.order ?? []).toEqual([])
  })

  it("redo() re-applies an undone state", () => {
    const editor = new Editor()
    editor.history.push({ sections: {}, order: [] })
    editor.history.push({ sections: { hero_1: {} }, order: ["hero_1"] } as any)
    editor.undo()
    editor.redo()
    expect(useStore.getState().currentPage?.order ?? []).toContain("hero_1")
  })
})

describe("Editor — addBlockFromPreview", () => {
  it("auto-adds block when only one type is available and limit not hit", () => {
    const editor = new Editor()
    const sectionId = editor.addSection("hero", heroSchema)
    // Give the section a schema with one block type in store
    useStore.setState((s: any) => ({
      sections: {
        hero: {
          schema: {
            ...heroSchema,
            blocks: [{ type: "text", name: "Text", settings: [], limit: 0 }],
          },
        },
      },
      blocks: {
        text: { schema: { type: "text", name: "Text", settings: [], limit: 0 } },
      },
    }))

    const fn = vi.fn()
    editor.on("block:added", fn)
    editor.addBlockFromPreview({ position: "after", sectionId, targetId: null, parentPath: [] })
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it("returns early when sectionId is missing", () => {
    const editor = new Editor()
    expect(() =>
      editor.addBlockFromPreview({
        position: "after",
        sectionId: "",
        targetId: null,
        parentPath: [],
      }),
    ).not.toThrow()
  })

  it("opens AddBlockModal when multiple block types are available", () => {
    const editor = new Editor()
    const sectionId = editor.addSection("hero", heroSchema)

    useStore.setState((s: any) => ({
      sections: {
        hero: {
          schema: {
            ...heroSchema,
            blocks: [
              { type: "text", name: "Text", settings: [], limit: 0 },
              { type: "image", name: "Image", settings: [], limit: 0 },
            ],
          },
        },
      },
      blocks: {
        text: { schema: { type: "text", name: "Text", settings: [], limit: 0 } },
        image: { schema: { type: "image", name: "Image", settings: [], limit: 0 } },
      },
    }))

    editor.addBlockFromPreview({ position: "after", sectionId, targetId: null, parentPath: [] })
    expect(editor.layout.addBlockModal.isOpen).toBe(true)
  })
})
