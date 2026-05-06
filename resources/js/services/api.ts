import config from "../config";
import { get, post } from "./apiFetch";

/**
 * API service for communicating with the Laravel page builder backend.
 *
 * All methods use the `apiFetch` wrapper which handles CSRF tokens,
 * JSON headers, and consistent error handling automatically.
 */
const api = {
  /**
   * Fetch a single page/template by slug.
   * GET {baseUrl}/{slug}.json
   */
  async getPage(slug: string) {
    return get<any>(`${config.baseUrl}/${slug}.json`);
  },

  /**
   * Render a block with given settings (live preview update).
   */
  async renderBlock(payload: Record<string, any>) {
    return post<{ html: string }>(`${config.baseUrl}/render-block`, payload);
  },

  /**
   * Render a section with given settings (live preview update).
   */
  async renderSection(payload: Record<string, any>) {
    return post<{ html: string }>(`${config.baseUrl}/render-section`, payload);
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
  ) {
    return post<any>(`${config.baseUrl}/${slug}`, {
      slug,
      data,
      meta,
      theme_settings: themeSettings,
    });
  },

  /**
   * Get theme settings (schema + values).
   */
  async getThemeSettings() {
    return get<{ schema: any[]; values: Record<string, any> }>(
      `${config.baseUrl}/theme-settings`,
    );
  },

  /**
   * Save theme settings values.
   */
  async saveThemeSettings(values: Record<string, any>) {
    return post<any>(`${config.baseUrl}/theme-settings`, { values });
  },

  /**
   * Get the preview URL for a page.
   *
   * Uses the real page URL with ?pb-editor=1 query parameter
   * so the preview renders through the actual site layout.
   */
  getPreviewUrl(slug: string): string {
    const params = new URLSearchParams({ "pb-editor": "1" });
    // Home page is served at "/", other pages at "/{slug}"
    const path = slug === "home" ? "" : `/${slug}`;
    const base = config.basePath === "/" ? "" : config.basePath;
    return `${base}${path}?${params.toString()}`;
  },
};

export default api;
