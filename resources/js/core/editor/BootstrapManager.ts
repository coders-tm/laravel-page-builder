/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import type { EventBus } from "./EventBus"
import type { PageManager } from "./PageManager"
import type { HistoryManager } from "./HistoryManager"
import type { NavigationManager } from "./NavigationManager"
import config from "@/config"
import { useStore } from "@/core/store/useStore"

/**
 * BootstrapManager — class-based startup/page-switch orchestration.
 */
export class BootstrapManager {
  private initialLoaded = false
  private loadedSlug: string | null = null

  constructor(
    private events: EventBus,
    private pages: PageManager,
    private history: HistoryManager,
    private navigation: NavigationManager,
  ) {}

  async loadInitialData(): Promise<void> {
    if (this.initialLoaded) return
    this.initialLoaded = true

    // Hydrate pages from config into the store now that setConfig() has run.
    // The store is created at module-load time (before setConfig is called),
    // so config.pages is [] at store creation. We sync it here.
    if (config.pages?.length > 0) {
      useStore.getState().setPages(config.pages)
    }

    await this.pages.loadSections()
    this.pages.loadBlocks()

    this.events.emit("bootstrap:loaded", {})
  }

  /**
   * Sync route state after initial data load.
   * - Redirect to first page when slug is missing
   * - Load page when slug changes
   */
  async syncRoute(slug: string | undefined, pageList: any[]): Promise<void> {
    if (!slug) {
      // In email mode, we don't want to redirect to a page slug automatically.
      // We also respect the root path if no pages are available.
      if (config.mode !== "email" && pageList.length > 0) {
        this.navigation.setPage(pageList[0].slug, { replace: true })
      }
      return
    }

    if (this.loadedSlug === slug) return

    this.loadedSlug = slug
    this.history.reset()
    await this.pages.load(slug)

    this.events.emit("bootstrap:page-loaded", { slug })
  }

  reset(): void {
    this.loadedSlug = null
    this.initialLoaded = false
  }
}
