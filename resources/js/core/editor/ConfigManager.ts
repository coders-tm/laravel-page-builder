/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import type { EditorConfig, PageBuilderConfig } from "@/config"

type EditorConfigShape = Partial<EditorConfig> & Partial<PageBuilderConfig>

/**
 * ConfigManager — class-based access to editor config.
 */
export class ConfigManager {
  constructor(
    private getCurrent: () => EditorConfigShape,
    private setCurrent: (next: EditorConfigShape) => void,
  ) {}

  get<T = any>(key: string): T {
    return (this.getCurrent() as any)[key] as T
  }

  getAll(): EditorConfigShape {
    return this.getCurrent()
  }

  set(key: string, value: any): void {
    const current = this.getCurrent()
    this.setCurrent({
      ...current,
      [key]: value,
    })
  }

  merge(patch: EditorConfigShape): void {
    const current = this.getCurrent()
    this.setCurrent({
      ...current,
      ...patch,
    })
  }
}
