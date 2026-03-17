import api from "@/services/api";
import type { IMessageBus } from "@/core/messaging/MessageBus";
import type { EventBus } from "./EventBus";
import { useStore } from "@/core/store/useStore";

/**
 * PreviewManager — handles all communication with the preview iframe.
 *
 * Responsibilities:
 *   - Server-side section re-rendering via API
 *   - Sending postMessage commands to the iframe
 *   - Live-text updates (no server round-trip)
 *   - Visibility toggling
 *   - Full page re-render for undo/redo
 *
 * @example
 * editor.preview.rerender('hero_123');
 * editor.preview.updateLiveText('hero_123.heading', 'Hello');
 * editor.preview.removeSection('hero_123');
 */
export class PreviewManager {
    private messageBus: IMessageBus | null = null;
    private rerenderTimer: ReturnType<typeof setTimeout> | null = null;
    private rerenderDelay = 400;
    private liveTextPaths: Set<string> = new Set();

    constructor(private events: EventBus) {}

    /** Bind a message bus (called once the iframe ref is available). */
    setMessageBus(bus: IMessageBus): void {
        this.messageBus = bus;
        bus.on("live-text-paths", ({ paths }: { paths: string[] }) => {
            this.liveTextPaths = new Set(paths);
        });
    }

    /** Returns true if the given path is bound to a data-live-text-setting element in the iframe. */
    isLiveTextSetting(path: string): boolean {
        return this.liveTextPaths.has(path);
    }

    /** Get the current message bus. */
    getMessageBus(): IMessageBus | null {
        return this.messageBus;
    }

    /** Send a raw message to the iframe. */
    send(type: string, payload: Record<string, any> = {}): void {
        this.messageBus?.send(type, payload);
    }

    /**
     * Render a single section via the API and push the HTML into the iframe.
     */
    async rerender(sectionId: string): Promise<void> {
        const state = useStore.getState();
        const currentPage = state.currentPage;
        if (!currentPage || !sectionId || !this.messageBus) return;

        const sec = currentPage.sections?.[sectionId];
        if (!sec) return;

        this.events.emit("preview:rerender", { sectionId });

        try {
            const { html } = await api.renderSection({
                section_id: sectionId,
                section_type: sec.type,
                settings: sec.settings || {},
                blocks: sec.blocks || {},
                order: sec.order || [],
            });

            const pageOrder = (currentPage.order || []).filter(
                (id: string) => !currentPage.sections?.[id]?.layout
            );

            this.messageBus.send("update-section-html", {
                sectionId,
                html,
                pageOrder,
            });

            this.messageBus.send("reorder-sections", { order: pageOrder });
        } catch {
            /* silently fail — preview will refresh on save */
        }
    }

    /**
     * Debounced re-render — coalesces rapid setting changes.
     */
    debouncedRerender(sectionId: string): void {
        if (this.rerenderTimer) {
            clearTimeout(this.rerenderTimer);
        }
        this.rerenderTimer = setTimeout(() => {
            this.rerender(sectionId);
        }, this.rerenderDelay);
    }

    /** Update a specific text element in the iframe instantly. */
    updateLiveText(path: string, value: string): void {
        this.messageBus?.send("update-live-text", { path, value });
    }

    /** Set a CSS custom property on the preview document root instantly. */
    updateCssVar(cssVar: string, value: string): void {
        this.messageBus?.send("update-css-var", { cssVar, value });
    }

    /** Remove a section from the iframe DOM. */
    removeSection(sectionId: string): void {
        this.messageBus?.send("remove-section", { sectionId });
    }

    /** Remove a block from the iframe DOM without a render round-trip. */
    removeBlock(
        sectionId: string,
        blockId: string,
        parentPath: string[] = []
    ): void {
        this.messageBus?.send("remove-block", {
            sectionId,
            blockId,
            parentPath,
        });
    }

    /** Reorder section DOM elements in the iframe. */
    reorderSections(order: string[]): void {
        this.messageBus?.send("reorder-sections", { order });
    }

    /** Reorder block DOM nodes within a section. */
    reorderBlocks(
        sectionId: string,
        order: string[],
        parentBlockId?: string | null
    ): void {
        this.messageBus?.send("reorder-blocks", {
            sectionId,
            order,
            parentBlockId: parentBlockId ?? null,
        });
    }

    /** Toggle visibility of a section or block in the preview. */
    toggleVisibility(
        kind: "section" | "block",
        sectionId: string,
        disabled: boolean,
        blockId?: string
    ): void {
        this.messageBus?.send("toggle-visibility", {
            kind,
            sectionId,
            blockId: blockId ?? null,
            disabled,
        });
    }

    /** Hover-highlight a section/block in the iframe. */
    hover(sectionId: string | null, blockId: string | null = null): void {
        this.messageBus?.send("hover-section", { sectionId, blockId });
    }

    /** Scroll the iframe to a section. */
    scrollToSection(sectionId: string): void {
        this.messageBus?.send("scroll-to-section", { sectionId });
    }

    /** Reload the entire preview. */
    reload(): void {
        this.messageBus?.send("reload-preview");
        this.events.emit("preview:reloaded");
    }

    /**
     * Re-render the entire page from a state snapshot (undo/redo).
     */
    async renderFullPage(pageSnapshot: any): Promise<void> {
        if (!pageSnapshot || !this.messageBus) return;

        const sections = pageSnapshot.sections || {};
        const order: string[] = (pageSnapshot.order || []).filter(
            (id: string) => !sections[id]?.layout
        );
        const layoutIds: string[] = (pageSnapshot.order || []).filter(
            (id: string) => !!sections[id]?.layout
        );

        // 1) Remove stale sections
        this.messageBus.send("replace-all-sections", { order, layoutIds });

        // 2) Re-render every section
        for (const sectionId of order) {
            const sec = sections[sectionId];
            if (!sec) continue;

            try {
                const { html } = await api.renderSection({
                    section_id: sectionId,
                    section_type: sec.type,
                    settings: sec.settings || {},
                    blocks: sec.blocks || {},
                    order: sec.order || [],
                });

                this.messageBus.send("update-section-html", {
                    sectionId,
                    html,
                    pageOrder: order,
                });
            } catch {
                /* silently fail for individual sections */
            }
        }

        // 3) Enforce final ordering
        this.messageBus.send("reorder-sections", { order });
    }
}
