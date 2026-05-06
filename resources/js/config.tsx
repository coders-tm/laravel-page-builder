import {
  BlockData,
  Page,
  SectionData,
  ThemeSettingsData,
} from "./types/page-builder";
import type { AssetProvider } from "./types/asset";
import laravelAssetProvider from "./services/laravelAssetProvider";

// Define the shape of our global configuration
export interface PageBuilderConfig {
  baseUrl: string;
  basePath: string;
  pages: Page[];
  sections: Record<string, SectionData>;
  blocks: Record<string, BlockData>;
  themeSettings: ThemeSettingsData;
  /**
   * Editor mode.
   *
   * - `"page"`  (default) — full page builder with page selector and sidebar tabs.
   * - `"email"` — hides the page selector and sidebar tab strip; intended for
   *               email/template editors where there is only one document to edit.
   */
  mode?: "page" | "email";
  /**
   * Additional query parameters to preserve during navigation.
   */
  preservedParams?: string[];
  fields: Record<
    string,
    | {
        type: "external";
        fetchList: () => Promise<
          Array<{
            label: string | number;
            value: string | number;
          }>
        >;
      }
    | ((args: {
        setting: any;
        value: any;
        onChange: (val: any) => void;
        container: HTMLElement;
      }) => void | string | HTMLElement)
  >;
  [key: string]: any;
}

/**
 * Asset and editor service configuration.
 *
 * Supports provider injection so future providers
 * (S3, Cloudinary, Unsplash) can be swapped without
 * changing any UI code.
 */
export interface EditorConfig {
  assets?: {
    provider?: AssetProvider;
  };
}

// Default configuration fallback
const config: PageBuilderConfig = {
  baseUrl: "/pagebuilder",
  basePath: "/",
  pages: [],
  sections: {},
  blocks: {},
  themeSettings: { schema: [], values: {} },
  fields: {},
};

/**
 * Default editor configuration (uses the Laravel asset provider).
 * Passed to createEditor() when no overrides are supplied.
 */
export const defaultConfig: EditorConfig = {
  assets: {
    provider: laravelAssetProvider,
  },
};

/**
 * Update the global configuration.
 * Call this during PageBuilder.init() to inject settings.
 */
export function setConfig(newConfig: Partial<PageBuilderConfig>) {
  Object.assign(config, newConfig);
}

export default config;
