/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import type { EventBus } from "./EventBus"
import type { SelectionManager } from "./SelectionManager"
import type { LayoutManager } from "./LayoutManager"
import config from "@/config"

interface NavigationAdapter {
  navigate: (path: string, options?: { replace?: boolean }) => void
  setSearchParams: (params: Record<string, string>) => void
  getSearchParams: () => URLSearchParams
  editorMode?: boolean
}

interface NavigationState {
  slug?: string
  device: string
  lang: string | null
  selectedSection: string | null
  blockPath: string[]
}

/**
 * NavigationManager — centralizes URL-driven editor navigation.
 *
 * React Router integration is provided via adapter methods set by the
 * React bridge hook. The manager owns navigation commands and keeps
 * SelectionManager + LayoutManager synchronized.
 */
export class NavigationManager {
  private adapter: NavigationAdapter | null = null

  private state: NavigationState = {
    slug: undefined,
    device: "desktop",
    lang: null,
    selectedSection: null,
    blockPath: [],
  }

  private listeners = new Set<() => void>()
  private version = 0

  constructor(
    private events: EventBus,
    private selection: SelectionManager,
    private layout: LayoutManager,
  ) {}

  setAdapter(adapter: NavigationAdapter | null): void {
    this.adapter = adapter
  }

  subscribe(listener: () => void): () => void {
    this.listeners.add(listener)
    return () => {
      this.listeners.delete(listener)
    }
  }

  getVersion(): number {
    return this.version
  }

  private notify(): void {
    this.version += 1
    for (const listener of this.listeners) {
      listener()
    }
  }

  private setState(next: Partial<NavigationState>): void {
    this.state = { ...this.state, ...next }
    this.notify()
  }

  get slug(): string | undefined {
    return this.state.slug
  }

  get device(): string {
    return this.state.device
  }

  get lang(): string | null {
    return this.state.lang
  }

  get selectedSection(): string | null {
    return this.state.selectedSection
  }

  get blockPath(): string[] {
    return this.state.blockPath
  }

  get selectedBlock(): string | null {
    const path = this.state.blockPath
    return path.length > 0 ? path[path.length - 1] : null
  }

  get parentBlockId(): string | null {
    const path = this.state.blockPath
    return path.length > 1 ? path[path.length - 2] : null
  }

  getSnapshot() {
    return {
      slug: this.slug,
      device: this.device,
      lang: this.lang,
      selectedSection: this.selectedSection,
      selectedBlock: this.selectedBlock,
      parentBlockId: this.parentBlockId,
      blockPath: this.blockPath,
    }
  }

  /**
   * Synchronize manager state from the current router location.
   * Called by the React Router bridge hook.
   */
  syncFromRoute(input: {
    slug?: string
    device: string
    lang: string | null
    selectedSection: string | null
    blockPath: string[]
  }): void {
    const prev = this.state
    const pageChanged = prev.slug !== input.slug
    const langChanged = prev.lang !== input.lang
    const changed =
      pageChanged ||
      langChanged ||
      prev.device !== input.device ||
      prev.selectedSection !== input.selectedSection ||
      prev.blockPath.join(",") !== input.blockPath.join(",")

    if (!changed) return

    this.setState({
      slug: input.slug,
      device: input.device,
      lang: input.lang,
      selectedSection: input.selectedSection,
      blockPath: [...input.blockPath],
    })

    this.layout.setDevice(input.device)
    this.selection.syncFromExternal(input.selectedSection, input.blockPath)

    this.events.emit("navigation:changed", this.getSnapshot())

    if (pageChanged || langChanged) {
      window.dispatchEvent(
        new CustomEvent("pagebuilder:page-change", {
          detail: { slug: input.slug ?? null, lang: input.lang },
        }),
      )
    }
  }

  setPage(slug: string, options?: { replace?: boolean }): void {
    const params = this.getPreservedParams()
    const search = new URLSearchParams(params).toString()
    const query = search ? `?${search}` : ""
    const path = slug === "home" ? "/" : `/${slug}`
    this.adapter?.navigate(`${path}${query}`, options)
  }

  private writeSelectionToUrl(sectionId: string | null, path: string[]): void {
    const params = this.getPreservedParams()

    if (this.device !== "desktop") params.device = this.device
    if (sectionId) params.section = sectionId
    if (path.length > 0) params.block = path.join(",")

    this.adapter?.setSearchParams(params)
  }

  private getPreservedParams(): Record<string, string> {
    const current = this.adapter?.getSearchParams()
    const params: Record<string, string> = {}

    if (current) {
      // Always preserve 'editor' and 'lang'
      if (current.has("editor")) {
        params.editor = current.get("editor")!
      }
      if (current.has("lang")) {
        params.lang = current.get("lang")!
      }

      // Preserve configurable params
      const preserved = ["editor", "lang", ...(config.preservedParams || [])]

      current.forEach((value, key) => {
        if (preserved.includes(key)) {
          params[key] = value
        }
      })
    } else {
      // Fallback if no adapter (should not happen in browser)
      params.editor = "true"
    }

    return params
  }

  setSelection(sectionId: string | null, blockPath: string[] = []): void {
    const path = [...blockPath]

    // Update internal stores immediately for responsive UI.
    if (sectionId === null) {
      this.selection.clear()
    } else if (path.length > 0) {
      this.selection.selectBlock(sectionId, path)
    } else {
      this.selection.selectSection(sectionId)
    }

    this.setState({ selectedSection: sectionId, blockPath: path })
    this.writeSelectionToUrl(sectionId, path)
  }

  setSection(sectionId: string | null, block: string | string[] | null = null): void {
    const path = Array.isArray(block)
      ? block
      : block
        ? String(block).split(",").filter(Boolean)
        : []

    this.setSelection(sectionId, path)
  }

  pushBlock(blockId: string): void {
    if (!this.selectedSection) return
    this.setSelection(this.selectedSection, [...this.blockPath, blockId])
  }

  clearSelection(): void {
    this.setSelection(null, [])
  }

  setDevice(device: string): void {
    this.layout.setDevice(device)
    this.setState({ device })

    const params = this.getPreservedParams()
    if (this.selectedSection) params.section = this.selectedSection
    if (this.blockPath.length > 0) params.block = this.blockPath.join(",")
    if (device !== "desktop") params.device = device
    this.adapter?.setSearchParams(params)
  }

  setLang(lang: string | null): void {
    this.setState({ lang })

    const params = this.getPreservedParams()
    if (this.selectedSection) params.section = this.selectedSection
    if (this.blockPath.length > 0) params.block = this.blockPath.join(",")
    if (this.device !== "desktop") params.device = this.device
    if (lang) params.lang = lang
    this.adapter?.setSearchParams(params)
  }

  goBack(): void {
    // UX rule: Back from settings should always return to the layout list
    // directly (no nested breadcrumb stepping).
    this.clearSelection()
  }
}
