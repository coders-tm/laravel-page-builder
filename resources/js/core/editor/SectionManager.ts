/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { useStore } from "@/core/store/useStore"
import type { EventBus } from "./EventBus"

/**
 * SectionManager — encapsulates all section CRUD operations.
 *
 * Delegates state mutations to the Zustand store and emits
 * events on the editor EventBus so that preview sync, history,
 * and external consumers can react declaratively.
 *
 * @example
 * editor.sections.add('hero', heroSchema);
 * editor.sections.remove('hero_123');
 * editor.sections.updateSettings('hero_123', { heading: 'Hello' });
 */
export class SectionManager {
  constructor(private events: EventBus) {}

  /* ── Queries ─────────────────────────────────────────────────────── */

  /** Get the registered section schemas (theme-level). */
  getSchemas() {
    return useStore.getState().sections
  }

  /** Alias of getSchemas() for a API feel. */
  getRegistered() {
    return this.getSchemas()
  }

  /** Get a specific section schema by type. */
  getSchema(type: string) {
    return useStore.getState().sections[type] ?? null
  }

  hasSchema(type: string): boolean {
    return !!this.getSchema(type)
  }

  /** Get all section instances on the current page. */
  getInstances() {
    return useStore.getState().currentPage?.sections ?? {}
  }

  /** Getter alias: get() or get(sectionId). */
  get(sectionId?: string) {
    return sectionId ? this.getInstance(sectionId) : this.getInstances()
  }

  /** Alias of getInstances(). */
  getAll() {
    return this.getInstances()
  }

  /** Get a specific section instance by ID. */
  getInstance(sectionId: string) {
    return useStore.getState().currentPage?.sections[sectionId] ?? null
  }

  /** Get the ordered list of section IDs. */
  getOrder() {
    return useStore.getState().currentPage?.order ?? []
  }

  /** Get only page-level (non-layout) section IDs in order. */
  getPageOrder() {
    const state = useStore.getState()
    const sections = state.currentPage?.sections ?? {}
    return (state.currentPage?.order ?? []).filter((id) => !(sections[id] as any)?.layout)
  }

  /** Register (or replace) a section schema at runtime. */
  register(type: string, sectionSchema: any): void {
    const state = useStore.getState()
    state.setSections({
      ...state.sections,
      [type]: sectionSchema,
    })
    this.events.emit("section:registered", { type })
  }

  /** Remove a section schema from the runtime registry. */
  unregister(type: string): void {
    const state = useStore.getState()
    const next = { ...state.sections }
    delete next[type]
    state.setSections(next)
    this.events.emit("section:unregistered", { type })
  }

  /* ── Mutations ───────────────────────────────────────────────────── */

  /** Add a new section from a schema. Returns the new section ID. */
  add(
    type: string,
    schema: any,
    insertIndex: number | null = null,
    presetIndex: number = 0,
  ): string {
    const sectionId = useStore.getState().addSection(type, schema, insertIndex, presetIndex)
    this.events.emit("section:added", { sectionId, type })
    return sectionId
  }

  /** Remove a section (page sections only — layout sections are protected). */
  remove(sectionId: string): void {
    useStore.getState().removeSection(sectionId)
    this.events.emit("section:removed", { sectionId })
  }

  /** Duplicate a section. Returns the new section ID. */
  duplicate(sectionId: string): string {
    const newId = useStore.getState().duplicateSection(sectionId)
    this.events.emit("section:duplicated", { sectionId, newId })
    return newId
  }

  /** Reorder sections (page-level only). */
  reorder(fromIndex: number, toIndex: number): void {
    useStore.getState().reorderSections(fromIndex, toIndex)
    this.events.emit("section:reordered", {
      order: this.getPageOrder(),
    })
  }

  /** Merge a settings patch into a section. */
  updateSettings(sectionId: string, values: Record<string, any>): void {
    useStore.getState().updateSectionSettings(sectionId, values)
    this.events.emit("section:settings-changed", { sectionId, values })
  }

  /** Toggle the disabled state of a section. */
  toggleDisabled(sectionId: string): void {
    useStore.getState().toggleSectionDisabled(sectionId)
    const section = this.getInstance(sectionId)
    this.events.emit("section:toggled", {
      sectionId,
      disabled: !!section?.disabled,
    })
  }

  /** Rename a section instance (custom display label). */
  rename(sectionId: string, name: string): void {
    useStore.getState().renameSectionInstance(sectionId, name)
    this.events.emit("section:renamed", { sectionId, name })
  }
}
