/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { Editor } from "./editor/Editor"
import type { EditorConfig, PageBuilderConfig } from "@/config"

/**
 * Create an editor instance with injected configuration.
 *
 * This is the primary factory function — it instantiates
 * the central Editor class with all managers and services.
 *
 * @example
 * const editor = createEditor({
 *   assets: { provider: laravelAssetProvider },
 *   sections: { … },
 *   blocks: { … },
 * });
 *
 * // Use the editor:
 * editor.sections.add('hero', heroSchema);
 * editor.on('section:added', ({ sectionId }) => { … });
 * editor.assets.list({ page: 1 });
 */
export function createEditor(
  config: Partial<EditorConfig> & Partial<PageBuilderConfig> = {},
): Editor {
  return new Editor(config)
}

// Re-export types for convenience
export type { Editor }
