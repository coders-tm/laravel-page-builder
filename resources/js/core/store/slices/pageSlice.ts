/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { StateCreator } from "zustand"
import api from "@/services/api"
import config from "@/config"
import { Page, PageMeta, SectionData, BlockData, ThemeSettingsData } from "@/types/page-builder"

export interface PageSlice {
  pages: any[]
  currentPage: Page | null
  currentSlug: string | null
  sections: Record<string, SectionData>
  blocks: Record<string, BlockData>
  loading: boolean
  saving: boolean

  /** Page meta fields (title, meta_title, meta_description, meta_keywords). */
  pageMeta: PageMeta

  /** Global theme settings (schema + values). */
  themeSettings: ThemeSettingsData

  setPages: (pages: any[]) => void
  setCurrentPage: (page: Page | null) => void
  setCurrentSlug: (slug: string | null) => void
  setSections: (sections: Record<string, SectionData>) => void
  setBlocks: (blocks: Record<string, BlockData>) => void
  setLoading: (loading: boolean) => void
  setSaving: (saving: boolean) => void

  /** Replace all page meta fields at once. */
  setPageMeta: (meta: PageMeta) => void
  /** Merge a partial patch into the current page meta. */
  updatePageMeta: (patch: Partial<PageMeta>) => void

  /** Replace theme settings values (preserves schema). */
  setThemeSettingsValues: (values: Record<string, any>) => void
  /** Update a single theme setting value. */
  updateThemeSettingValue: (key: string, value: any) => void

  loadPage: (slug: string) => Promise<void>
  loadSections: () => Promise<void>
  loadBlocks: () => void
  savePage: () => Promise<void>
  saveThemeSettings: () => Promise<void>
}

export const createPageSlice: StateCreator<PageSlice, [["zustand/immer", never]]> = (set, get) => ({
  pages: config.pages || [],
  currentPage: null,
  currentSlug: null,
  sections: config.sections || {},
  blocks: config.blocks || {},
  loading: false,
  saving: false,
  pageMeta: {},
  themeSettings: config.themeSettings || { schema: [], values: {} },

  setPages: (pages) => set({ pages }),
  setCurrentPage: (page) => set({ currentPage: page }),
  setCurrentSlug: (slug) => set({ currentSlug: slug }),
  setSections: (sections) => set({ sections }),
  setBlocks: (blocks) => set({ blocks }),
  setLoading: (loading) => set({ loading }),
  setSaving: (saving) => set({ saving }),

  setPageMeta: (meta) => set({ pageMeta: meta }),
  updatePageMeta: (patch) =>
    set((state) => {
      state.pageMeta = { ...state.pageMeta, ...patch }
    }),

  setThemeSettingsValues: (values) =>
    set((state) => {
      state.themeSettings.values = values
    }),
  updateThemeSettingValue: (key, value) =>
    set((state) => {
      state.themeSettings.values[key] = value
    }),

  loadPage: async (slug: string) => {
    set({ loading: true })
    try {
      const raw = await api.getPage(slug)

      // ── Flatten layout zones into the unified sections map ─────────
      // The API returns:
      //   layout: {
      //     type: "page",
      //     header: { sections: { "header": {...} }, order: ["header"] },
      //     footer: { sections: { "footer": {...} }, order: ["footer"] },
      //   }
      //
      // We stamp each layout section with `layout: true` and
      // `layoutZone: "header"|"footer"` so the editor knows where to
      // put them, then merge everything into one flat `sections` map.
      const pageSections: Record<string, any> = {
        ...(raw.sections || {}),
      }

      const headerKeys: string[] = []
      const footerKeys: string[] = []

      const ingestZone = (
        zone: { sections?: Record<string, any>; order?: string[] } | undefined,
        zoneName: "header" | "footer",
        bucket: string[],
      ) => {
        if (!zone?.sections) return
        const zoneOrder = zone.order ?? Object.keys(zone.sections)
        for (const key of zoneOrder) {
          const ls = zone.sections[key]
          if (!ls) continue
          pageSections[key] = {
            ...ls,
            layout: true,
            layoutZone: zoneName,
          }
          bucket.push(key)
        }
      }

      ingestZone(raw.layout?.header, "header", headerKeys)
      ingestZone(raw.layout?.footer, "footer", footerKeys)

      // ── Build flat order: header zone, page sections, footer zone ──
      const pageOrder: string[] = raw.order || []
      const flatOrder = [...headerKeys, ...pageOrder, ...footerKeys]

      const data = {
        ...raw,
        sections: pageSections,
        order: flatOrder,
        layout: undefined, // stripped — re-assembled on save
      }

      // ── Extract page meta ──────────────────────────────────────────
      const meta: PageMeta = {
        title: raw.title ?? "",
        meta_title: raw.meta?.meta_title ?? "",
        meta_description: raw.meta?.meta_description ?? "",
        meta_keywords: raw.meta?.meta_keywords ?? "",
      }

      set({
        currentPage: data,
        currentSlug: slug,
        pageMeta: meta,
        selectedSection: null,
        selectedBlock: null,
        selectedBlockPath: [],
      } as any)
    } catch (err) {
      console.error("Failed to load page:", err)
    } finally {
      set({ loading: false })
    }
  },

  loadSections: async () => {
    if (Object.keys(config.sections || {}).length > 0) {
      set({ sections: config.sections })
    }
    if (config.themeSettings) {
      set({ themeSettings: config.themeSettings })
    }
  },

  loadBlocks: () => {
    if (Object.keys(config.blocks || {}).length > 0) {
      set({ blocks: config.blocks })
    }
  },

  savePage: async () => {
    const state = get()
    if (!state.currentSlug || !state.currentPage) return
    set({ saving: true })
    try {
      // ── Re-split sections back to the API zone shape ──────────────
      // Layout sections (layout: true) are placed back into
      // layout.header.sections or layout.footer.sections based on
      // their `layoutZone` stamp set during loadPage.
      // Page sections stay in sections/order.
      const pageSections: Record<string, any> = {}
      const headerSections: Record<string, any> = {}
      const footerSections: Record<string, any> = {}
      const headerOrder: string[] = []
      const footerOrder: string[] = []
      const layoutIds = new Set<string>()

      for (const [id, sec] of Object.entries(state.currentPage.sections)) {
        if ((sec as any).layout) {
          // Strip editor-only flags before sending to API
          const { layout, layoutZone, ...rest } = sec as any
          layoutIds.add(id)
          if (layoutZone === "footer") {
            footerSections[id] = rest
            footerOrder.push(id)
          } else {
            // Default to header zone for any unrecognised zone
            headerSections[id] = rest
            headerOrder.push(id)
          }
        } else {
          pageSections[id] = sec
        }
      }

      // Preserve the zone order from the flat order array
      const flatOrder = state.currentPage.order
      headerOrder.sort((a, b) => flatOrder.indexOf(a) - flatOrder.indexOf(b))
      footerOrder.sort((a, b) => flatOrder.indexOf(a) - flatOrder.indexOf(b))

      // Strip layout IDs from the page order
      const pageOrder = flatOrder.filter((id) => !layoutIds.has(id))

      // Omit DB-only fields (title, meta) — these are sent separately as `meta`.
      const { title: _title, meta: _meta, ...pageData } = state.currentPage as any
      const payload = {
        ...pageData,
        sections: pageSections,
        order: pageOrder,
        layout: {
          type: "page",
          header: { sections: headerSections, order: headerOrder },
          footer: { sections: footerSections, order: footerOrder },
        },
      }

      await api.savePage(state.currentSlug, payload, state.pageMeta, state.themeSettings.values)
    } catch (err) {
      console.error("Failed to save:", err)
    } finally {
      set({ saving: false })
    }
  },

  saveThemeSettings: async () => {
    const state = get()
    set({ saving: true })
    try {
      await api.saveThemeSettings(state.themeSettings.values)
    } catch (err) {
      console.error("Failed to save theme settings:", err)
    } finally {
      set({ saving: false })
    }
  },
})
