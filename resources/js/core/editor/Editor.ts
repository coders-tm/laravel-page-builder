import { EventBus } from "./EventBus";
import { SectionManager } from "./SectionManager";
import { BlockManager } from "./BlockManager";
import { SelectionManager } from "./SelectionManager";
import { LayoutManager } from "./LayoutManager";
import { ConfigManager } from "./ConfigManager";
import { NavigationManager } from "./NavigationManager";
import { BootstrapManager } from "./BootstrapManager";
import { ShortcutManager } from "./ShortcutManager";
import { InteractionManager } from "./InteractionManager";
import { HistoryManager } from "./HistoryManager";
import { PreviewManager } from "./PreviewManager";
import { PageManager } from "./PageManager";
import { AssetService } from "@/services/assetService";
import { FieldRegistry } from "@/core/registry/FieldRegistry";
import { defaultConfig } from "@/config";
import type { EditorConfig, PageBuilderConfig } from "@/config";
import { getNestedBlock } from "@/core/store/useStore";

/**
 * Central Editor instance — the single entry point to all editor
 * functionality.
 *
 * Inspired by GrapesJS's `Editor` pattern: a single object that
 * holds all managers, services, config, and an event bus.  Any
 * component (React or plain JS) can call methods directly on the
 * editor instance instead of threading props through multiple
 * layers of components and hooks.
 *
 * @example
 * const editor = new Editor(config);
 *
 * // Section operations
 * editor.sections.add('hero', heroSchema);
 * editor.sections.remove('hero_123');
 * editor.sections.updateSettings('hero_123', { heading: 'Hi' });
 *
 * // Block operations
 * editor.blocks.add('hero_123', 'text', { text: 'Hello' });
 *
 * // History
 * editor.undo();
 * editor.redo();
 *
 * // Events
 * editor.on('section:added', ({ sectionId }) => { … });
 *
 * // Preview
 * editor.preview.rerender('hero_123');
 */
export class Editor {
    /* ── Managers ─────────────────────────────────────────────────────── */

    /** Event bus for decoupled communication. */
    readonly events: EventBus;

    /** Section CRUD operations. */
    readonly sections: SectionManager;

    /** Block CRUD operations. */
    readonly blocks: BlockManager;

    /** Section/block selection state. */
    readonly selection: SelectionManager;

    /** Editor layout/UI state. */
    readonly layout: LayoutManager;

    /** Router navigation and URL state. */
    readonly navigation: NavigationManager;

    /** Bootstrap/startup orchestration. */
    readonly bootstrap: BootstrapManager;

    /** Keyboard shortcuts. */
    readonly shortcuts: ShortcutManager;

    /** Editor interaction helpers (iframe hover/focus). */
    readonly interaction: InteractionManager;

    /** Undo/redo history. */
    readonly history: HistoryManager;

    /** Iframe preview communication. */
    readonly preview: PreviewManager;

    /** Page loading/saving and meta. */
    readonly pages: PageManager;

    /* ── Services ─────────────────────────────────────────────────────── */

    /** Asset management service. */
    readonly assets: AssetService;

    /** Field type registry. */
    readonly fields: typeof FieldRegistry = FieldRegistry;

    /** Config module. */
    readonly config: ConfigManager;

    /* ── Config ───────────────────────────────────────────────────────── */

    private editorConfig: Partial<EditorConfig> & Partial<PageBuilderConfig>;

    constructor(
        config: Partial<EditorConfig> & Partial<PageBuilderConfig> = {}
    ) {
        this.editorConfig = config;

        // ── 1. Event bus (created first — all managers depend on it) ──
        this.events = new EventBus();

        // ── 2. Managers ──────────────────────────────────────────────
        this.sections = new SectionManager(this.events);
        this.blocks = new BlockManager(this.events);
        this.selection = new SelectionManager(this.events);
        this.layout = new LayoutManager(this.events);
        this.history = new HistoryManager(this.events);
        this.preview = new PreviewManager(this.events);
        this.pages = new PageManager(this.events);
        this.navigation = new NavigationManager(
            this.events,
            this.selection,
            this.layout
        );
        this.bootstrap = new BootstrapManager(
            this.events,
            this.pages,
            this.history,
            this.navigation
        );
        this.shortcuts = new ShortcutManager();
        this.interaction = new InteractionManager();

        // ── 3. Services ──────────────────────────────────────────────
        const merged = {
            ...defaultConfig,
            ...config,
            assets: {
                ...defaultConfig.assets,
                ...config.assets,
            },
        };

        const assetProvider = merged.assets!.provider!;
        this.assets = new AssetService(assetProvider);

        this.config = new ConfigManager(
            () => this.editorConfig,
            (next) => {
                this.editorConfig = next;
            }
        );

        // ── 4. Wire event→preview synchronization ────────────────────
        this._wirePreviewEvents();
    }

    /* ── Internal wiring ─────────────────────────────────────────────── */

    /**
     * Subscribe to all mutation events and forward them to the
     * PreviewManager. Called once in the constructor so any component
     * can trigger mutations without needing to set up its own listeners.
     */
    private _wirePreviewEvents(): void {
        this.events.on("section:added", ({ sectionId }: { sectionId: string }) => {
            this.preview.debouncedRerender(sectionId);
        });

        this.events.on("section:removed", ({ sectionId }: { sectionId: string }) => {
            this.preview.removeSection(sectionId);
        });

        this.events.on("section:duplicated", ({ newId }: { newId: string | undefined }) => {
            if (newId) this.preview.debouncedRerender(newId);
        });

        this.events.on("section:reordered", ({ order }: { order: string[] }) => {
            this.preview.reorderSections(order);
        });

        this.events.on(
            "section:settings-changed",
            ({ sectionId, values }: { sectionId: string; values: Record<string, any> }) => {
                const keys = Object.keys(values || {});
                if (keys.length === 1) {
                    const key = keys[0];
                    const path = `${sectionId}.${key}`;
                    if (this.preview.isLiveTextSetting(path)) {
                        // Live-text update: push text to DOM instantly, no server re-render.
                        this.preview.updateLiveText(path, values[key]);
                        return;
                    }
                }
                this.preview.debouncedRerender(sectionId);
            }
        );

        this.events.on("section:toggled", ({ sectionId, disabled }: { sectionId: string; disabled: boolean }) => {
            this.preview.toggleVisibility("section", sectionId, disabled);
        });

        this.events.on("block:added", ({ sectionId }: { sectionId: string }) => {
            this.preview.debouncedRerender(sectionId);
        });

        this.events.on(
            "block:removed",
            ({ sectionId, blockId, parentPath }: { sectionId: string; blockId: string; parentPath?: string[] }) => {
                this.preview.removeBlock(sectionId, blockId, parentPath || []);
            }
        );

        this.events.on("block:duplicated", ({ sectionId }: { sectionId: string }) => {
            this.preview.debouncedRerender(sectionId);
        });

        this.events.on(
            "block:reordered",
            ({ sectionId, order, parentPath }: { sectionId: string; order: string[]; parentPath?: string[] }) => {
                const parentBlockId =
                    parentPath && parentPath.length > 0
                        ? parentPath[parentPath.length - 1]
                        : null;
                this.preview.reorderBlocks(sectionId, order, parentBlockId);
            }
        );

        this.events.on(
            "block:settings-changed",
            ({ sectionId, blockId, values }: { sectionId: string; blockId: string; values: Record<string, any> }) => {
                const keys = Object.keys(values || {});
                if (keys.length === 1) {
                    const key = keys[0];
                    const path = `${blockId}.${key}`;
                    if (this.preview.isLiveTextSetting(path)) {
                        // Live-text update: push text to DOM instantly, no server re-render.
                        this.preview.updateLiveText(path, values[key]);
                        return;
                    }
                }
                this.preview.debouncedRerender(sectionId);
            }
        );

        this.events.on(
            "block:toggled",
            ({ sectionId, blockId, disabled }: { sectionId: string; blockId: string; disabled: boolean }) => {
                this.preview.toggleVisibility("block", sectionId, disabled, blockId);
            }
        );

        this.events.on(
            "block:moved",
            ({ fromSectionId, toSectionId }: { fromSectionId: string; toSectionId: string }) => {
                this.preview.debouncedRerender(fromSectionId);
                if (toSectionId !== fromSectionId) {
                    this.preview.debouncedRerender(toSectionId);
                }
            }
        );

        this.events.on(
            "theme:setting-changed",
            ({ cssVar, value }: { cssVar: string | null; value: any }) => {
                if (cssVar) {
                    this.preview.updateCssVar(cssVar, value);
                }
            }
        );
    }

    /* ── Convenience shortcuts (delegate to EventBus) ────────────────── */

    /** Subscribe to an editor event. */
    on(event: string, listener: (...args: any[]) => void): () => void {
        return this.events.on(event, listener);
    }

    /** Subscribe to an editor event for a single invocation. */
    once(event: string, listener: (...args: any[]) => void): () => void {
        return this.events.once(event, listener);
    }

    /** Emit an editor event. */
    emit(event: string, ...args: any[]): void {
        this.events.emit(event, ...args);
    }

    /* ── Config access ───────────────────────────────────────────────── */

    /** Get a config value. */
    getConfig<K extends string>(key: K): any {
        return this.config.get(key);
    }

    /** Get the full editor config. */
    getFullConfig(): Partial<EditorConfig> & Partial<PageBuilderConfig> {
        return this.config.getAll();
    }

    /* ── GrapesJS-style shortcut API ─────────────────────────────────── */

    addSection(type: string, schema: any, insertIndex: number | null = null): string {
        return this.sections.add(type, schema, insertIndex);
    }

    addBlock(
        sectionId: string,
        type: string,
        defaults: Record<string, any> = {},
        afterBlockId: string | null = null,
        parentPath: string[] = []
    ): string {
        return this.blocks.add(
            sectionId,
            type,
            defaults,
            afterBlockId,
            parentPath
        );
    }

    getSections() {
        return this.sections.getInstances();
    }

    getBlocks(sectionId?: string) {
        if (sectionId) {
            return this.blocks.getInstances(sectionId);
        }
        const sections = this.sections.getInstances();
        const all: Record<string, any> = {};
        for (const [id, section] of Object.entries(sections)) {
            all[id] = (section as any)?.blocks ?? {};
        }
        return all;
    }

    getSettings() {
        return {
            page: this.pages.getMeta(),
            theme: this.pages.getThemeSettings(),
        };
    }

    selectSection(sectionId: string | null): void {
        this.navigation.setSection(sectionId);
    }

    selectBlock(sectionId: string, blockPath: string[]): void {
        this.navigation.setSelection(sectionId, blockPath);
    }

    clearSelection(): void {
        this.navigation.clearSelection();
    }

    setDevice(device: string): void {
        this.navigation.setDevice(device);
    }

    setPage(slug: string, options?: { replace?: boolean }): void {
        this.navigation.setPage(slug, options);
    }

    renameSection(sectionId: string, name: string): void {
        this.sections.rename(sectionId, name);
    }

    renameBlock(
        sectionId: string,
        blockId: string,
        name: string,
        parentPath: string[] = []
    ): void {
        this.blocks.rename(sectionId, blockId, name, parentPath);
    }

    updateSection(sectionId: string, values: Record<string, any>): void {
        this.sections.updateSettings(sectionId, values);
    }

    updateBlock(
        sectionId: string,
        blockId: string,
        values: Record<string, any>,
        parentPath: string[] = []
    ): void {
        this.blocks.updateSettings(sectionId, blockId, values, parentPath);
    }

    removeSection(sectionId: string): void {
        this.sections.remove(sectionId);
    }

    removeBlock(
        sectionId: string,
        blockId: string,
        parentPath: string[] = []
    ): void {
        this.blocks.remove(sectionId, blockId, parentPath);
    }

    registerSection(type: string, sectionSchema: any): void {
        this.sections.register(type, sectionSchema);
    }

    registerBlock(type: string, blockSchema: any): void {
        this.blocks.register(type, blockSchema);
    }

    /**
     * Undo the last change. Restores the previous page snapshot and
     * triggers a full preview re-render.
     */
    undo(): void {
        const restored = this.history.undo();
        if (!restored) return;
        this.pages.setCurrentPage(restored);
        void this.preview.renderFullPage(restored);
    }

    /**
     * Redo the last undone change. Re-applies the snapshot and
     * triggers a full preview re-render.
     */
    redo(): void {
        const restored = this.history.redo();
        if (!restored) return;
        this.pages.setCurrentPage(restored);
        void this.preview.renderFullPage(restored);
    }

    /**
     * Handle an "add block" request originating from the preview iframe.
     *
     * Resolves the addable block types for the target context, then either
     * adds a block directly (if only one type is available) or opens the
     * AddBlockModal for the user to choose.
     */
    addBlockFromPreview(payload: {
        position: "before" | "after";
        sectionId: string;
        targetId: string | null;
        parentPath: string[];
    }): void {
        const sectionId = payload?.sectionId;
        if (!sectionId) return;

        const section = this.sections.getInstance(sectionId) as any;
        if (!section) return;

        const parentPath = Array.isArray(payload?.parentPath)
            ? payload.parentPath
            : [];
        const blockTypes = this.blocks.getAddableTypes(sectionId, parentPath);
        if (blockTypes.length === 0) return;

        const parentNode =
            parentPath.length > 0
                ? getNestedBlock(section.blocks || {}, parentPath)
                : section;
        const targetOrder: string[] =
            parentNode?.order || Object.keys(parentNode?.blocks || {});

        const targetId = payload?.targetId || null;
        const position = payload?.position === "before" ? "before" : "after";
        const targetIdx = targetId ? targetOrder.indexOf(targetId) : -1;
        const insertAtStartToken = "__pb_insert_at_start__";

        const afterBlockId =
            targetId && targetIdx !== -1
                ? position === "after"
                    ? targetId
                    : targetIdx > 0
                    ? targetOrder[targetIdx - 1]
                    : insertAtStartToken
                : null;

        if (blockTypes.length === 1) {
            const blockType = blockTypes[0];
            const defaults: Record<string, any> = {};
            (blockType.settings || []).forEach((s: any) => {
                if (s.default !== undefined) defaults[s.id] = s.default;
            });
            const blockId = this.addBlock(
                sectionId,
                blockType.type,
                defaults,
                afterBlockId,
                parentPath
            );
            this.selectBlock(sectionId, [...parentPath, blockId]);
            return;
        }

        this.layout.openAddBlockModal(
            blockTypes,
            sectionId,
            parentPath,
            afterBlockId
        );
    }

    /* ── GrapesJS-style module aliases ───────────────────────────────── */

    get Layout() {
        return this.layout;
    }

    get Sections() {
        return this.sections;
    }

    get Blocks() {
        return this.blocks;
    }

    get Selection() {
        return this.selection;
    }

    get Navigation() {
        return this.navigation;
    }

    get Bootstrap() {
        return this.bootstrap;
    }

    get Shortcuts() {
        return this.shortcuts;
    }

    get Interaction() {
        return this.interaction;
    }

    get Config() {
        return this.config;
    }

    /* ── Lifecycle ───────────────────────────────────────────────────── */

    /** Signal that the editor is fully initialised and ready. */
    ready(): void {
        this.events.emit("editor:ready");
    }

    /** Tear down the editor and release resources. */
    destroy(): void {
        this.events.emit("editor:destroyed");
        this.events.clear();
    }
}
