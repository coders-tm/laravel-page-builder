/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { NavigationManager } from "@/core/editor/NavigationManager"
import { SelectionManager } from "@/core/editor/SelectionManager"
import { LayoutManager } from "@/core/editor/LayoutManager"
import { EventBus } from "@/core/editor/EventBus"
import { useStore } from "@/core/store/useStore"

function makeAdapter() {
  return {
    navigate: vi.fn(),
    setSearchParams: vi.fn(),
    getSearchParams: vi.fn(() => new URLSearchParams("editor=true")),
    editorMode: true,
  }
}

beforeEach(() => {
  useStore.setState({
    selectedSection: null,
    selectedBlock: null,
    selectedBlockPath: [],
    currentPage: null,
  } as any)
})

describe("NavigationManager", () => {
  let events: EventBus
  let selection: SelectionManager
  let layout: LayoutManager
  let nav: NavigationManager

  beforeEach(() => {
    events = new EventBus()
    selection = new SelectionManager(events)
    layout = new LayoutManager(events)
    nav = new NavigationManager(events, selection, layout)
  })

  it("default device is desktop", () => {
    expect(nav.device).toBe("desktop")
  })

  it("default slug is undefined", () => {
    expect(nav.slug).toBeUndefined()
  })

  /* ── setPage ──────────────────────────────────────────────────── */
  it("setPage('home') navigates to '/'", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setPage("home")
    expect(adapter.navigate).toHaveBeenCalledWith(expect.stringContaining("/"), undefined)
  })

  it("setPage('about') navigates to '/about'", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setPage("about")
    expect(adapter.navigate).toHaveBeenCalledWith(expect.stringContaining("/about"), undefined)
  })

  it("setPage() with replace:true passes the option through", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setPage("about", { replace: true })
    expect(adapter.navigate).toHaveBeenCalledWith(expect.any(String), { replace: true })
  })

  it("setPage() preserves the 'editor' search param in the URL", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setPage("contact")
    const url: string = adapter.navigate.mock.calls[0][0]
    expect(url).toContain("editor=true")
  })

  /* ── setSelection ─────────────────────────────────────────────── */
  it("setSelection() updates internal state", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", ["block_a"])
    expect(nav.selectedSection).toBe("hero_1")
    expect(nav.blockPath).toEqual(["block_a"])
  })

  it("setSelection(null) clears selection", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", [])
    nav.setSelection(null)
    expect(nav.selectedSection).toBeNull()
  })

  /* ── setSection ───────────────────────────────────────────────── */
  it("setSection() parses a string block arg", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSection("hero_1", "block_a,block_b")
    expect(nav.blockPath).toEqual(["block_a", "block_b"])
  })

  it("setSection() accepts an array block arg", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSection("hero_1", ["block_a"])
    expect(nav.blockPath).toEqual(["block_a"])
  })

  /* ── pushBlock ────────────────────────────────────────────────── */
  it("pushBlock() appends a block id to the current path", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", ["block_a"])
    nav.pushBlock("block_b")
    expect(nav.blockPath).toEqual(["block_a", "block_b"])
  })

  it("pushBlock() is a no-op when no section is selected", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.pushBlock("block_a")
    expect(nav.blockPath).toEqual([])
  })

  /* ── clearSelection ───────────────────────────────────────────── */
  it("clearSelection() resets section and path", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", ["block_a"])
    nav.clearSelection()
    expect(nav.selectedSection).toBeNull()
    expect(nav.blockPath).toEqual([])
  })

  /* ── setDevice ────────────────────────────────────────────────── */
  it("setDevice() updates device and writes to URL", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setDevice("mobile")
    expect(nav.device).toBe("mobile")
    expect(adapter.setSearchParams).toHaveBeenCalled()
  })

  /* ── setLang ──────────────────────────────────────────────────── */
  it("setLang() updates lang and includes it in search params", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setLang("fr")
    expect(nav.lang).toBe("fr")
    const params: Record<string, string> = adapter.setSearchParams.mock.calls[0][0]
    expect(params.lang).toBe("fr")
  })

  /* ── syncFromRoute ────────────────────────────────────────────── */
  it("syncFromRoute() short-circuits when inputs are identical", () => {
    const fn = vi.fn()
    events.on("navigation:changed", fn)
    const input = {
      slug: "home",
      device: "desktop",
      lang: null,
      selectedSection: null,
      blockPath: [],
    }
    nav.syncFromRoute(input)
    nav.syncFromRoute(input) // same — no change
    expect(fn).toHaveBeenCalledTimes(1) // only the first call fires
  })

  it("syncFromRoute() emits navigation:changed on page change", () => {
    const fn = vi.fn()
    events.on("navigation:changed", fn)
    nav.syncFromRoute({
      slug: "home",
      device: "desktop",
      lang: null,
      selectedSection: null,
      blockPath: [],
    })
    nav.syncFromRoute({
      slug: "about",
      device: "desktop",
      lang: null,
      selectedSection: null,
      blockPath: [],
    })
    expect(fn).toHaveBeenCalledTimes(2)
  })

  it("syncFromRoute() dispatches pagebuilder:page-change on page change", () => {
    const fn = vi.fn()
    window.addEventListener("pagebuilder:page-change", fn)
    // First call: sets initial slug (undefined → "home"), fires event
    nav.syncFromRoute({
      slug: "home",
      device: "desktop",
      lang: null,
      selectedSection: null,
      blockPath: [],
    })
    // Second call: different slug fires again
    nav.syncFromRoute({
      slug: "about",
      device: "desktop",
      lang: null,
      selectedSection: null,
      blockPath: [],
    })
    // At least one page-change event must fire when slug changes
    expect(fn.mock.calls.length).toBeGreaterThanOrEqual(1)
    window.removeEventListener("pagebuilder:page-change", fn)
  })

  /* ── goBack ───────────────────────────────────────────────────── */
  it("goBack() calls clearSelection()", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", [])
    nav.goBack()
    expect(nav.selectedSection).toBeNull()
  })

  /* ── computed getters ─────────────────────────────────────────── */
  it("selectedBlock returns the last element of blockPath", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", ["block_a", "block_b"])
    expect(nav.selectedBlock).toBe("block_b")
  })

  it("parentBlockId returns the second-to-last element of blockPath", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", ["block_a", "block_b"])
    expect(nav.parentBlockId).toBe("block_a")
  })

  it("parentBlockId is null when blockPath has only one element", () => {
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setSelection("hero_1", ["block_a"])
    expect(nav.parentBlockId).toBeNull()
  })

  /* ── subscribe / version ──────────────────────────────────────── */
  it("subscribe() notifies listeners on state change", () => {
    const fn = vi.fn()
    nav.subscribe(fn)
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setDevice("tablet")
    expect(fn).toHaveBeenCalled()
  })

  it("subscribe() returns an unsubscribe function", () => {
    const fn = vi.fn()
    const off = nav.subscribe(fn)
    off()
    const adapter = makeAdapter()
    nav.setAdapter(adapter)
    nav.setDevice("tablet")
    expect(fn).not.toHaveBeenCalled()
  })
})
