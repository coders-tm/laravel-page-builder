/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import api from "@/services/api"

vi.mock("@/services/apiFetch", () => ({
  get: vi.fn(),
  post: vi.fn(),
}))

import { get, post } from "@/services/apiFetch"

const mockGet = vi.mocked(get)
const mockPost = vi.mocked(post)

beforeEach(() => {
  vi.clearAllMocks()
  mockGet.mockResolvedValue({} as any)
  mockPost.mockResolvedValue({} as any)
  // Reset window.pageBuilderConfig to a known state
  ;(window as any).pageBuilderConfig = {
    baseUrl: "/page-builder",
    basePath: "/",
  }
})

describe("api service", () => {
  /* ── getPage ──────────────────────────────────────────────────── */
  it("getPage() calls GET with the correct URL", async () => {
    await api.getPage("home")
    expect(mockGet).toHaveBeenCalledWith(expect.stringContaining("/home.json"))
  })

  it("getPage() appends ?lang= when lang is provided", async () => {
    await api.getPage("home", "fr")
    const url: string = mockGet.mock.calls[0][0]
    expect(url).toContain("lang=fr")
  })

  it("getPage() does not append lang param when lang is null", async () => {
    await api.getPage("home", null)
    const url: string = mockGet.mock.calls[0][0]
    expect(url).not.toContain("lang")
  })

  /* ── renderSection ────────────────────────────────────────────── */
  it("renderSection() calls POST to /render-section with payload", async () => {
    const payload = {
      section_id: "hero_1",
      section_type: "hero",
      settings: {},
      blocks: {},
      order: [],
      disabled: false,
      slug: "home",
    }
    await api.renderSection(payload)
    expect(mockPost).toHaveBeenCalledWith(expect.stringContaining("/render-section"), payload)
  })

  it("renderSection() includes lang in body when provided", async () => {
    const payload = {
      section_id: "hero_1",
      section_type: "hero",
      settings: {},
      blocks: {},
      order: [],
      disabled: false,
      slug: "home",
    }
    await api.renderSection(payload, "es")
    const body = mockPost.mock.calls[0][1] as any
    expect(body.lang).toBe("es")
  })

  /* ── renderBlock ──────────────────────────────────────────────── */
  it("renderBlock() calls POST to /render-block with payload", async () => {
    const payload = { block_id: "b1", block_type: "text" }
    await api.renderBlock(payload)
    expect(mockPost).toHaveBeenCalledWith(expect.stringContaining("/render-block"), payload)
  })

  /* ── savePage ─────────────────────────────────────────────────── */
  it("savePage() calls POST with slug, data, meta, and theme_settings", async () => {
    await api.savePage("home", { sections: {} }, { meta_title: "Test" }, { color: "red" })
    const body = mockPost.mock.calls[0][1] as any
    expect(body.slug).toBe("home")
    expect(body.data).toEqual({ sections: {} })
    expect(body.meta).toEqual({ meta_title: "Test" })
    expect(body.theme_settings).toEqual({ color: "red" })
  })

  it("savePage() includes lang in body when provided", async () => {
    await api.savePage("home", {}, {}, {}, "de")
    const body = mockPost.mock.calls[0][1] as any
    expect(body.lang).toBe("de")
  })

  /* ── getThemeSettings ─────────────────────────────────────────── */
  it("getThemeSettings() calls GET /theme-settings", async () => {
    await api.getThemeSettings()
    expect(mockGet).toHaveBeenCalledWith(expect.stringContaining("/theme-settings"))
  })

  /* ── saveThemeSettings ────────────────────────────────────────── */
  it("saveThemeSettings() calls POST /theme-settings with { values }", async () => {
    await api.saveThemeSettings({ primary: "#000" })
    expect(mockPost).toHaveBeenCalledWith(expect.stringContaining("/theme-settings"), {
      values: { primary: "#000" },
    })
  })

  /* ── getPreviewUrl ────────────────────────────────────────────── */
  it("getPreviewUrl('home') returns '/?pb-editor=1'", () => {
    const url = api.getPreviewUrl("home")
    expect(url).toBe("?pb-editor=1")
  })

  it("getPreviewUrl('about') returns '/about?pb-editor=1'", () => {
    const url = api.getPreviewUrl("about")
    expect(url).toContain("/about")
    expect(url).toContain("pb-editor=1")
  })

  it("getPreviewUrl('about', 'fr') includes lang=fr", () => {
    const url = api.getPreviewUrl("about", "fr")
    expect(url).toContain("lang=fr")
  })
})
