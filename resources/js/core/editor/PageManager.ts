import { useStore } from "@/core/store/useStore"
import type { EventBus } from "./EventBus"
import type { PageMeta, SettingSchema, ThemeSettingsData } from "@/types/page-builder"

/**
 * PageManager — encapsulates page-level operations.
 *
 * Handles loading/saving pages, page meta, and theme settings.
 *
 * @example
 * await editor.pages.load('home');
 * await editor.pages.save();
 * editor.pages.updateMeta({ meta_title: 'New Title' });
 */
export class PageManager {
  constructor(private events: EventBus) {}

  /* ── Queries ─────────────────────────────────────────────────────── */

  /** Get all page entries. */
  getAll() {
    return useStore.getState().pages
  }

  /** Get the currently loaded page. */
  getCurrent() {
    return useStore.getState().currentPage
  }

  /** Get the current page slug. */
  getCurrentSlug() {
    return useStore.getState().currentSlug
  }

  /** Get page meta fields. */
  getMeta(): PageMeta {
    return useStore.getState().pageMeta
  }

  /** Get theme settings (schema + values). */
  getThemeSettings(): ThemeSettingsData {
    return useStore.getState().themeSettings
  }

  /** Check if the editor is currently loading. */
  isLoading(): boolean {
    return useStore.getState().loading
  }

  /** Check if a save is in progress. */
  isSaving(): boolean {
    return useStore.getState().saving
  }

  /* ── Mutations ───────────────────────────────────────────────────── */

  /** Load all section schemas. */
  async loadSections(): Promise<void> {
    await useStore.getState().loadSections()
  }

  /** Load all block schemas. */
  loadBlocks(): void {
    useStore.getState().loadBlocks()
  }

  /** Load a specific page by slug. */
  async load(slug: string): Promise<void> {
    await useStore.getState().loadPage(slug)
    this.events.emit("page:loaded", { slug })
  }

  /** Save the current page (includes theme settings in the same request). */
  async save(): Promise<void> {
    await useStore.getState().savePage()
    const slug = useStore.getState().currentSlug
    this.events.emit("page:saved", { slug })
  }

  /** Replace the current page state (used by undo/redo). */
  setCurrentPage(page: any): void {
    useStore.getState().setCurrentPage(page)
  }

  /** Update page meta fields. */
  updateMeta(patch: Partial<PageMeta>): void {
    useStore.getState().updatePageMeta(patch)
    this.events.emit("page:meta-updated", { meta: patch })
  }

  /** Update a single theme setting value. */
  updateThemeSetting(key: string, value: SettingSchema["default"]): void {
    useStore.getState().updateThemeSettingValue(key, value)
    const setting = this.findSchemaSetting(key)
    this.events.emit("theme:setting-changed", {
      key,
      value,
      cssVar: setting?.css_var ?? null,
    })
  }

  /** Reset a single theme setting to its schema default. */
  resetThemeSetting(key: string): void {
    const setting = this.findSchemaSetting(key)
    if (setting?.default !== undefined) {
      this.updateThemeSetting(key, setting.default)
    }
  }

  /** Reset every theme setting to its schema default and sync preview in one pass. */
  resetAllThemeSettings(): void {
    const { schema } = useStore.getState().themeSettings
    const defaults: Record<string, SettingSchema["default"]> = {}
    const cssVars: Record<string, string> = {}

    for (const group of schema) {
      for (const setting of group.settings) {
        if (setting.default === undefined) continue
        const key = setting.key ?? setting.id
        defaults[key] = setting.default
        if (setting.css_var) {
          cssVars[setting.css_var] = setting.default
        }
      }
    }

    useStore.getState().setThemeSettingsValues(defaults)
    this.events.emit("theme:settings-reset", { cssVars })
  }

  /* ── Private helpers ─────────────────────────────────────────────── */

  private findSchemaSetting(key: string): SettingSchema | undefined {
    const { schema } = useStore.getState().themeSettings
    for (const group of schema) {
      for (const setting of group.settings) {
        if ((setting.key ?? setting.id) === key) {
          return setting
        }
      }
    }
    return undefined
  }

  /** Save theme settings to the API. */
  async saveThemeSettings(): Promise<void> {
    await useStore.getState().saveThemeSettings()
  }
}
