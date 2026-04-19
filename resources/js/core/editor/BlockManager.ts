import { useStore } from "@/core/store/useStore";
import { getNestedBlock } from "@/core/store/useStore";
import type { BlockData, BlockSchema } from "@/types/page-builder";
import type { EventBus } from "./EventBus";

/**
 * BlockManager — encapsulates all block CRUD operations.
 *
 * All mutations go through the Zustand store; events are emitted
 * on the editor EventBus for side-effects (preview, history, etc.).
 *
 * @example
 * editor.blocks.add('hero_123', 'text', { text: 'Hello' });
 * editor.blocks.remove('hero_123', 'text_456');
 * editor.blocks.updateSettings('hero_123', 'text_456', { text: 'World' });
 */
export class BlockManager {
  constructor(private events: EventBus) {}

  private isLocalBlockEntry(entry: {
    type: string;
    [key: string]: any;
  }): boolean {
    return Object.keys(entry).some((k) => k !== "type");
  }

  private isThemeMode(
    raw: Array<{ type: string; [key: string]: any }> | undefined | null,
  ): boolean {
    return Array.isArray(raw) && raw.length === 1 && raw[0].type === "@theme";
  }

  private resolveSchema(
    blockType: string,
    parentRawBlocks:
      | Array<{ type: string; [key: string]: any }>
      | undefined
      | null,
    themeBlocks: Record<string, BlockData>,
  ): BlockSchema | null {
    const raw = parentRawBlocks || [];

    if (this.isThemeMode(raw)) {
      return themeBlocks[blockType]?.schema ?? null;
    }

    const entry = raw.find((b) => b.type === blockType);
    if (entry) {
      if (this.isLocalBlockEntry(entry)) {
        return entry as unknown as BlockSchema;
      }
      return themeBlocks[blockType]?.schema ?? null;
    }

    return themeBlocks[blockType]?.schema ?? null;
  }

  private resolveParentRawBlocks(
    sectionId: string,
    parentPath: string[] = [],
  ): Array<{ type: string; [key: string]: any }> {
    const state = useStore.getState();
    const section = state.currentPage?.sections?.[sectionId];
    if (!section) return [];

    const sectionSchema = state.sections?.[section.type]?.schema;
    let parentRawBlocks = (sectionSchema?.blocks as any[]) || [];
    let currentBlocks = section.blocks || {};

    for (const blockId of parentPath) {
      const block = currentBlocks[blockId];
      if (!block) return [];

      const blockSchema = this.resolveSchema(
        block.type,
        parentRawBlocks,
        state.blocks,
      );

      parentRawBlocks = (blockSchema?.blocks as any[]) || [];
      currentBlocks = block.blocks || {};
    }

    return parentRawBlocks;
  }

  /* ── Queries ─────────────────────────────────────────────────────── */

  /** Get the registered block schemas (theme-level). */
  getSchemas() {
    return useStore.getState().blocks;
  }

  /** Alias of getSchemas() for registry-style access. */
  getRegistered() {
    return this.getSchemas();
  }

  /** Get a specific block schema by type. */
  getSchema(type: string) {
    return useStore.getState().blocks[type] ?? null;
  }

  hasSchema(type: string): boolean {
    return !!this.getSchema(type);
  }

  /** Get all block instances within a section. */
  getInstances(sectionId: string) {
    return useStore.getState().currentPage?.sections[sectionId]?.blocks ?? {};
  }

  /** Get the ordered list of block IDs for a section or nested container. */
  getOrder(sectionId: string, parentPath: string[] = []): string[] {
    const section = useStore.getState().currentPage?.sections[sectionId];
    if (!section) return [];

    const parentBlock =
      parentPath.length > 0
        ? getNestedBlock(section.blocks ?? {}, parentPath)
        : section;

    return parentBlock?.order || Object.keys(parentBlock?.blocks || {});
  }

  /** Getter alias. */
  get(sectionId: string, blockPath: string[] = []) {
    return blockPath.length > 0
      ? this.getInstance(sectionId, blockPath)
      : this.getInstances(sectionId);
  }

  /** Register (or replace) a block schema at runtime. */
  register(type: string, blockSchema: any): void {
    const state = useStore.getState();
    state.setBlocks({
      ...state.blocks,
      [type]: blockSchema,
    });
    this.events.emit("block:registered", { type });
  }

  /** Remove a block schema from the runtime registry. */
  unregister(type: string): void {
    const state = useStore.getState();
    const next = { ...state.blocks };
    delete next[type];
    state.setBlocks(next);
    this.events.emit("block:unregistered", { type });
  }

  /** Get a specific block instance (supports nested paths). */
  getInstance(sectionId: string, blockPath: string[]) {
    const sectionBlocks = this.getInstances(sectionId);
    if (blockPath.length === 0) return null;
    return getNestedBlock(sectionBlocks, blockPath) ?? null;
  }

  /** Get block schemas addable under a section root or a nested container path. */
  getAddableTypes(sectionId: string, parentPath: string[] = []): BlockSchema[] {
    const state = useStore.getState();
    const raw = this.resolveParentRawBlocks(sectionId, parentPath);
    const themeBlocks = state.blocks;

    if (this.isThemeMode(raw)) {
      return Object.values(themeBlocks).map((bd) => bd.schema);
    }

    if (raw.length === 0) return [];

    return raw
      .filter((entry) => entry.type !== "@theme")
      .map((entry) => {
        if (this.isLocalBlockEntry(entry)) {
          return entry as unknown as BlockSchema;
        }
        return (
          themeBlocks[entry.type]?.schema ?? (entry as unknown as BlockSchema)
        );
      });
  }

  /* ── Mutations ───────────────────────────────────────────────────── */

  /**
   * Add a new block to a section.
   * Returns the new block ID.
   */
    add(
    sectionId: string,
    blockType: string,
    defaults: Record<string, any> = {},
    afterBlockId: string | null = null,
    parentPath: string[] = [],
    initialBlocks?: Record<string, any>,
    initialOrder?: string[]
  ): string {
    const blockId = useStore
      .getState()
      .addBlock(sectionId, blockType, defaults, afterBlockId, parentPath, initialBlocks, initialOrder);
    this.events.emit("block:added", {
      sectionId,
      blockId,
      type: blockType,
      parentPath,
    });
    return blockId;
  }

  /** Remove a block from a section. */
  remove(sectionId: string, blockId: string, parentPath: string[] = []): void {
    useStore.getState().removeBlock(sectionId, blockId, parentPath);
    this.events.emit("block:removed", { sectionId, blockId, parentPath });
  }

  /** Duplicate a block. Returns the new block ID. */
  duplicate(
    sectionId: string,
    blockId: string,
    parentPath: string[] = [],
  ): string {
    const newId = useStore
      .getState()
      .duplicateBlock(sectionId, blockId, parentPath);
    this.events.emit("block:duplicated", {
      sectionId,
      blockId,
      newId,
      parentPath,
    });
    return newId;
  }

  /** Reorder blocks within a section (or nested container). */
  reorder(
    sectionId: string,
    newOrder: string[],
    parentPath: string[] = [],
  ): void {
    useStore.getState().reorderBlocks(sectionId, newOrder, parentPath);
    this.events.emit("block:reordered", {
      sectionId,
      order: newOrder,
      parentPath,
    });
  }

  /** Merge a settings patch into a block. */
  updateSettings(
    sectionId: string,
    blockId: string,
    values: Record<string, any>,
    parentPath: string[] = [],
  ): void {
    useStore
      .getState()
      .updateBlockSettings(sectionId, blockId, values, parentPath);
    this.events.emit("block:settings-changed", {
      sectionId,
      blockId,
      values,
      parentPath,
    });
  }

  /** Toggle the disabled state of a block. */
  toggleDisabled(
    sectionId: string,
    blockId: string,
    parentPath: string[] = [],
  ): void {
    useStore.getState().toggleBlockDisabled(sectionId, blockId, parentPath);

    // Read new state after toggle
    const section = useStore.getState().currentPage?.sections[sectionId];
    const parentBlock =
      parentPath.length > 0
        ? getNestedBlock(section?.blocks ?? {}, parentPath)
        : section;
    const disabled = !!parentBlock?.blocks?.[blockId]?.disabled;

    this.events.emit("block:toggled", {
      sectionId,
      blockId,
      disabled,
      parentPath,
    });
  }

  /** Move a block between sections or containers. */
  move(
    fromSectionId: string,
    toSectionId: string,
    blockId: string,
    fromPath: string[],
    toPath: string[],
    toIndex: number,
  ): void {
    useStore
      .getState()
      .moveBlock(
        fromSectionId,
        toSectionId,
        blockId,
        fromPath,
        toPath,
        toIndex,
      );
    this.events.emit("block:moved", {
      fromSectionId,
      toSectionId,
      blockId,
      fromPath,
      toPath,
    });
  }

  /** Rename a block instance (custom display label). */
  rename(
    sectionId: string,
    blockId: string,
    name: string,
    parentPath: string[] = [],
  ): void {
    useStore
      .getState()
      .renameBlockInstance(sectionId, blockId, name, parentPath);
    this.events.emit("block:renamed", {
      sectionId,
      blockId,
      name,
      parentPath,
    });
  }
}
