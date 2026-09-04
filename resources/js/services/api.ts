/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import config from "../config"
import { get, post } from "./apiFetch"

/**
 * API service for communicating with the Laravel page builder backend.
 *
 * All methods use the `apiFetch` wrapper which handles CSRF tokens,
 * JSON headers, and consistent error handling automatically.
 */
const api = {
  /**
   * Fetch a single page/template by slug.
   * GET {baseUrl}/{slug}.json?lang={lang}
   */
  async getPage(slug: string, lang?: string | null) {
    const params = new URLSearchParams()
    if (lang) params.set("lang", lang)
    const qs = params.toString() ? `?${params.toString()}` : ""
    return get<any>(`${config.baseUrl}/${slug}.json${qs}`)
  },

  /**
   * Render a block with given settings (live preview update).
   */
  async renderBlock(payload: Record<string, any>, lang?: string | null) {
    const body = lang ? { ...payload, lang } : payload
    return post<{ html: string }>(`${config.baseUrl}/render-block`, body)
  },

  /**
   * Render a section with given settings (live preview update).
   */
  async renderSection(payload: Record<string, any>, lang?: string | null) {
    const body = lang ? { ...payload, lang } : payload
    return post<{ html: string }>(`${config.baseUrl}/render-section`, body)
  },

  /**
   * Save a page/template by slug.
   * POST {baseUrl}/{slug}
   */
  async savePage(
    slug: string,
    data: any,
    meta?: any,
    themeSettings?: Record<string, any>,
    lang?: string | null,
  ) {
    const body: Record<string, any> = {
      slug,
      data,
      meta,
      theme_settings: themeSettings,
    }
    if (lang) body.lang = lang
    return post<any>(`${config.baseUrl}/${slug}`, body)
  },

  /**
   * Get theme settings (schema + values).
   */
  async getThemeSettings() {
    return get<{ schema: any[]; values: Record<string, any> }>(`${config.baseUrl}/theme-settings`)
  },

  /**
   * Save theme settings values.
   */
  async saveThemeSettings(values: Record<string, any>) {
    return post<any>(`${config.baseUrl}/theme-settings`, { values })
  },

  /**
   * Get the preview URL for a page.
   *
   * Uses the real page URL with ?pb-editor=1 query parameter
   * so the preview renders through the actual site layout.
   */
  getPreviewUrl(slug: string, lang?: string | null): string {
    const params = new URLSearchParams({ "pb-editor": "1" })
    if (lang) params.set("lang", lang)
    // Home page is served at "/", other pages at "/{slug}"
    const path = slug === "home" ? "" : `/${slug}`
    const base = config.basePath === "/" ? "" : config.basePath
    return `${base}${path}?${params.toString()}`
  },
}

export default api
