/**
 * Editor event map — defines every event the editor can emit
 * and its payload shape.
 *
 * This gives consumers IntelliSense for `editor.on(…)` and
 * serves as living documentation of the event contract.
 */

export interface EditorEventMap {
    /* ── Section events ──────────────────────────────────────────────── */
    "section:added": { sectionId: string; type: string };
    "section:removed": { sectionId: string };
    "section:duplicated": { sectionId: string; newId: string };
    "section:reordered": { order: string[] };
    "section:settings-changed": {
        sectionId: string;
        values: Record<string, any>;
    };
    "section:toggled": { sectionId: string; disabled: boolean };
    "section:renamed": { sectionId: string; name: string };

    /* ── Block events ────────────────────────────────────────────────── */
    "block:added": {
        sectionId: string;
        blockId: string;
        type: string;
        parentPath: string[];
    };
    "block:removed": { sectionId: string; blockId: string; parentPath: string[] };
    "block:duplicated": {
        sectionId: string;
        blockId: string;
        newId: string;
        parentPath: string[];
    };
    "block:reordered": {
        sectionId: string;
        order: string[];
        parentPath: string[];
    };
    "block:settings-changed": {
        sectionId: string;
        blockId: string;
        values: Record<string, any>;
        parentPath: string[];
    };
    "block:toggled": {
        sectionId: string;
        blockId: string;
        disabled: boolean;
        parentPath: string[];
    };
    "block:renamed": {
        sectionId: string;
        blockId: string;
        name: string;
        parentPath: string[];
    };
    "block:moved": {
        fromSectionId: string;
        toSectionId: string;
        blockId: string;
        fromPath: string[];
        toPath: string[];
    };

    /* ── Selection events ────────────────────────────────────────────── */
    "selection:section-changed": { sectionId: string | null };
    "selection:block-changed": {
        sectionId: string | null;
        blockPath: string[];
    };
    "selection:cleared": {};

    /* ── History events ──────────────────────────────────────────────── */
    "history:undo": {};
    "history:redo": {};
    "history:snapshot": {};
    "history:reset": {};

    /* ── Page events ─────────────────────────────────────────────────── */
    "page:loaded": { slug: string };
    "page:saved": { slug: string };
    "page:changed": { slug: string };
    "page:meta-updated": { meta: Record<string, any> };

    /* ── Theme events ────────────────────────────────────────────────── */
    "theme:setting-changed": { key: string; value: any; cssVar: string | null };

    /* ── Preview events ──────────────────────────────────────────────── */
    "preview:rerender": { sectionId: string };
    "preview:reloaded": {};

    /* ── Layout events ───────────────────────────────────────────────── */
    "layout:device-changed": { device: string };
    "layout:inspector-toggled": { enabled: boolean };
    "layout:sidebar-tab-changed": { tab: string };

    /* ── Navigation / bootstrap events ──────────────────────────────── */
    "navigation:changed": {
        slug?: string;
        device: string;
        selectedSection: string | null;
        selectedBlock: string | null;
        parentBlockId: string | null;
        blockPath: string[];
    };
    "bootstrap:loaded": {};
    "bootstrap:page-loaded": { slug: string };

    /* ── Lifecycle events ────────────────────────────────────────────── */
    "editor:ready": {};
    "editor:destroyed": {};

    /* ── Catch-all for external plugins ──────────────────────────────── */
    [key: string]: any;
}
