/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { PreviewManager } from "@/core/editor/PreviewManager"
import { EventBus } from "@/core/editor/EventBus"
import { useStore } from "@/core/store/useStore"

// Mock api module
vi.mock("@/services/api", () => ({
  default: {
    renderSection: vi.fn().mockResolvedValue({ html: "<section>rendered</section>" }),
    getPreviewUrl: vi.fn(() => "http://localhost/preview"),
  },
}))

function makeBus() {
  return {
    send: vi.fn(),
    on: vi.fn(),
  }
}

function makeManager() {
  const events = new EventBus()
  const manager = new PreviewManager(events)
  return { events, manager }
}

beforeEach(() => {
  useStore.setState({
    currentSlug: "home",
    currentPage: {
      sections: {
        hero_1: {
          type: "hero",
          settings: { heading: "Hi" },
          blocks: {},
          order: [],
          disabled: false,
        },
      },
      order: ["hero_1"],
    },
  } as any)
})

describe("PreviewManager", () => {
  /* ── setMessageBus ────────────────────────────────────────────── */
  it("setMessageBus() registers the live-text-paths listener", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    expect(bus.on).toHaveBeenCalledWith("live-text-paths", expect.any(Function))
  })

  it("isLiveTextSetting() returns false before any paths are received", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    expect(manager.isLiveTextSetting("hero_1.heading")).toBe(false)
  })

  it("isLiveTextSetting() returns true after paths are registered", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    // Simulate receiving live-text-paths message
    const [, callback] = bus.on.mock.calls[0]
    callback({ paths: ["hero_1.heading"] })
    expect(manager.isLiveTextSetting("hero_1.heading")).toBe(true)
    expect(manager.isLiveTextSetting("hero_1.other")).toBe(false)
  })

  /* ── send ─────────────────────────────────────────────────────── */
  it("send() delegates to messageBus.send()", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.send("test-type", { key: "value" })
    expect(bus.send).toHaveBeenCalledWith("test-type", { key: "value" })
  })

  it("send() does nothing when no messageBus is set", () => {
    const { manager } = makeManager()
    expect(() => manager.send("test")).not.toThrow()
  })

  /* ── rerender ─────────────────────────────────────────────────── */
  it("rerender() calls api.renderSection and sends update-section-html", async () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    await manager.rerender("hero_1")
    expect(bus.send).toHaveBeenCalledWith(
      "update-section-html",
      expect.objectContaining({ sectionId: "hero_1" }),
    )
    expect(bus.send).toHaveBeenCalledWith("reorder-sections", expect.any(Object))
  })

  it("rerender() emits preview:rerender event", async () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("preview:rerender", fn)
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    await manager.rerender("hero_1")
    expect(fn).toHaveBeenCalledWith({ sectionId: "hero_1" })
  })

  it("rerender() early-returns when no messageBus", async () => {
    const { manager } = makeManager()
    await expect(manager.rerender("hero_1")).resolves.not.toThrow()
  })

  it("rerender() early-returns when section does not exist", async () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    await manager.rerender("nonexistent_section")
    expect(bus.send).not.toHaveBeenCalled()
  })

  it("rerender() silently catches API errors", async () => {
    const api = await import("@/services/api")
    vi.mocked(api.default.renderSection).mockRejectedValueOnce(new Error("Network error"))
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    await expect(manager.rerender("hero_1")).resolves.not.toThrow()
  })

  /* ── debouncedRerender ────────────────────────────────────────── */
  it("debouncedRerender() coalesces rapid calls", async () => {
    vi.useFakeTimers()
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.debouncedRerender("hero_1")
    manager.debouncedRerender("hero_1")
    manager.debouncedRerender("hero_1")
    vi.advanceTimersByTime(500)
    await vi.runAllTimersAsync()
    // rerender should have been called once (not 3 times)
    const calls = bus.send.mock.calls.filter((c: any[]) => c[0] === "update-section-html")
    expect(calls.length).toBe(1)
    vi.useRealTimers()
  })

  /* ── live text / css ──────────────────────────────────────────── */
  it("updateLiveText() sends update-live-text message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.updateLiveText("hero_1.heading", "New Heading")
    expect(bus.send).toHaveBeenCalledWith("update-live-text", {
      path: "hero_1.heading",
      value: "New Heading",
    })
  })

  it("updateCssVar() sends update-css-var message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.updateCssVar("--primary", "#ff0000")
    expect(bus.send).toHaveBeenCalledWith("update-css-var", {
      cssVar: "--primary",
      value: "#ff0000",
    })
  })

  it("updateCssVars() sends update-css-vars message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.updateCssVars({ "--primary": "#ff0000", "--secondary": "#00ff00" })
    expect(bus.send).toHaveBeenCalledWith("update-css-vars", {
      vars: { "--primary": "#ff0000", "--secondary": "#00ff00" },
    })
  })

  /* ── section/block DOM operations ─────────────────────────────── */
  it("removeSection() sends remove-section message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.removeSection("hero_1")
    expect(bus.send).toHaveBeenCalledWith("remove-section", { sectionId: "hero_1" })
  })

  it("removeBlock() sends remove-block message with parentPath", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.removeBlock("hero_1", "block_a", ["container_1"])
    expect(bus.send).toHaveBeenCalledWith("remove-block", {
      sectionId: "hero_1",
      blockId: "block_a",
      parentPath: ["container_1"],
    })
  })

  it("reorderSections() sends reorder-sections message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.reorderSections(["hero_1", "hero_2"])
    expect(bus.send).toHaveBeenCalledWith("reorder-sections", { order: ["hero_1", "hero_2"] })
  })

  it("reorderBlocks() sends reorder-blocks with parentBlockId", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.reorderBlocks("hero_1", ["b1", "b2"], "container_1")
    expect(bus.send).toHaveBeenCalledWith("reorder-blocks", {
      sectionId: "hero_1",
      order: ["b1", "b2"],
      parentBlockId: "container_1",
    })
  })

  it("toggleVisibility() sends toggle-visibility message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.toggleVisibility("section", "hero_1", true)
    expect(bus.send).toHaveBeenCalledWith(
      "toggle-visibility",
      expect.objectContaining({ kind: "section", sectionId: "hero_1", disabled: true }),
    )
  })

  it("hover() sends hover-section message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.hover("hero_1", "block_a")
    expect(bus.send).toHaveBeenCalledWith("hover-section", {
      sectionId: "hero_1",
      blockId: "block_a",
    })
  })

  it("scrollToSection() sends scroll-to-section message", () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    manager.scrollToSection("hero_1")
    expect(bus.send).toHaveBeenCalledWith("scroll-to-section", { sectionId: "hero_1" })
  })

  it("reload() sends reload-preview and emits preview:reloaded", () => {
    const { events, manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    const fn = vi.fn()
    events.on("preview:reloaded", fn)
    manager.reload()
    // reload-preview may or may not pass a second arg — just check the message type
    const reloadCall = bus.send.mock.calls.find((c: any[]) => c[0] === "reload-preview")
    expect(reloadCall).toBeDefined()
    expect(fn).toHaveBeenCalledTimes(1)
  })

  /* ── renderFullPage ───────────────────────────────────────────── */
  it("renderFullPage() sends replace-all-sections then renders each section", async () => {
    const { manager } = makeManager()
    const bus = makeBus()
    manager.setMessageBus(bus as any)
    const snapshot = useStore.getState().currentPage
    await manager.renderFullPage(snapshot)
    expect(bus.send).toHaveBeenCalledWith("replace-all-sections", expect.any(Object))
    expect(bus.send).toHaveBeenCalledWith(
      "update-section-html",
      expect.objectContaining({ sectionId: "hero_1" }),
    )
    expect(bus.send).toHaveBeenCalledWith("reorder-sections", expect.any(Object))
  })

  it("renderFullPage() early-returns when no messageBus", async () => {
    const { manager } = makeManager()
    const snapshot = useStore.getState().currentPage
    await expect(manager.renderFullPage(snapshot)).resolves.not.toThrow()
  })
})
