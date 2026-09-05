/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { PageManager } from "@/core/editor/PageManager"
import { EventBus } from "@/core/editor/EventBus"
import { useStore } from "@/core/store/useStore"

vi.mock("@/services/api", () => ({
  default: {
    getPage: vi.fn().mockResolvedValue({ sections: {}, order: [], meta: {} }),
    savePage: vi.fn().mockResolvedValue({}),
    getThemeSettings: vi.fn().mockResolvedValue({ schema: [], values: {} }),
    saveThemeSettings: vi.fn().mockResolvedValue({}),
  },
}))

const themeSchema = [
  {
    group: "colors",
    settings: [
      {
        id: "primary_color",
        key: "primary_color",
        type: "color",
        default: "#ffffff",
        css_var: "--primary",
      },
      { id: "secondary_color", key: "secondary_color", type: "color", default: "#000000" },
    ],
  },
]

function resetStore() {
  useStore.setState({
    currentPage: { sections: {}, order: [] },
    currentSlug: "home",
    pages: [],
    loading: false,
    saving: false,
    sections: {},
    blocks: {},
    pageMeta: { meta_title: "Test", meta_description: "", meta_image: "" },
    themeSettings: {
      schema: themeSchema,
      values: { primary_color: "#ffffff", secondary_color: "#000000" },
    },
    selectedSection: null,
    selectedBlock: null,
    selectedBlockPath: [],
  } as any)
}

function makeManager() {
  const events = new EventBus()
  const manager = new PageManager(events)
  return { events, manager }
}

beforeEach(resetStore)

describe("PageManager", () => {
  /* ── queries ──────────────────────────────────────────────────── */
  it("getCurrent() returns currentPage from store", () => {
    const { manager } = makeManager()
    expect(manager.getCurrent()).toEqual({ sections: {}, order: [] })
  })

  it("getCurrentSlug() returns currentSlug from store", () => {
    const { manager } = makeManager()
    expect(manager.getCurrentSlug()).toBe("home")
  })

  it("getMeta() returns pageMeta from store", () => {
    const { manager } = makeManager()
    expect(manager.getMeta().meta_title).toBe("Test")
  })

  it("getThemeSettings() returns themeSettings from store", () => {
    const { manager } = makeManager()
    expect(manager.getThemeSettings().schema).toEqual(themeSchema)
  })

  it("isLoading() and isSaving() reflect store booleans", () => {
    const { manager } = makeManager()
    expect(manager.isLoading()).toBe(false)
    expect(manager.isSaving()).toBe(false)
  })

  /* ── mutations ────────────────────────────────────────────────── */
  it("setCurrentPage() updates store currentPage", () => {
    const { manager } = makeManager()
    manager.setCurrentPage({ sections: { hero: {} }, order: ["hero"] } as any)
    expect(useStore.getState().currentPage?.order).toContain("hero")
  })

  it("updateMeta() merges patch and emits page:meta-updated", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("page:meta-updated", fn)
    manager.updateMeta({ meta_title: "New Title" })
    expect(useStore.getState().pageMeta.meta_title).toBe("New Title")
    expect(fn).toHaveBeenCalledWith({ meta: { meta_title: "New Title" } })
  })

  /* ── theme settings ───────────────────────────────────────────── */
  it("updateThemeSetting() updates value and emits theme:setting-changed with cssVar", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("theme:setting-changed", fn)
    manager.updateThemeSetting("primary_color", "#ff0000")
    expect(useStore.getState().themeSettings.values.primary_color).toBe("#ff0000")
    expect(fn).toHaveBeenCalledWith(
      expect.objectContaining({ key: "primary_color", value: "#ff0000", cssVar: "--primary" }),
    )
  })

  it("updateThemeSetting() emits null cssVar for settings without css_var", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("theme:setting-changed", fn)
    manager.updateThemeSetting("secondary_color", "#aaaaaa")
    expect(fn).toHaveBeenCalledWith(expect.objectContaining({ cssVar: null }))
  })

  it("resetThemeSetting() restores schema default", () => {
    const { manager } = makeManager()
    manager.updateThemeSetting("primary_color", "#ff0000")
    manager.resetThemeSetting("primary_color")
    expect(useStore.getState().themeSettings.values.primary_color).toBe("#ffffff")
  })

  it("resetAllThemeSettings() resets all settings and emits theme:settings-reset with cssVars map", () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("theme:settings-reset", fn)
    manager.updateThemeSetting("primary_color", "#ff0000")
    manager.resetAllThemeSettings()
    expect(useStore.getState().themeSettings.values.primary_color).toBe("#ffffff")
    expect(fn).toHaveBeenCalledWith(
      expect.objectContaining({ cssVars: { "--primary": "#ffffff" } }),
    )
  })

  /* ── async operations (mocked) ────────────────────────────────── */
  it("save() emits page:saved", async () => {
    const { events, manager } = makeManager()
    const fn = vi.fn()
    events.on("page:saved", fn)
    // Mock store.savePage
    useStore.setState({ savePage: vi.fn().mockResolvedValue(undefined) } as any)
    await manager.save()
    expect(fn).toHaveBeenCalled()
  })
})
