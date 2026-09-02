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
