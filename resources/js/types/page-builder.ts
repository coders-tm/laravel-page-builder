export interface Page {
    id: string;
    title: string;
    slug: string;
    /**
     * Flat map of ALL sections used by the editor — both page sections and
     * layout sections (header, footer, …).
     * Layout sections carry `layout: true` and `layoutZone: "header"|"footer"`;
     * their map key is the position slug (e.g. "header", "footer").
     */
    sections: Record<string, SectionInstance>;
    /**
     * Flat ordered array of ALL section IDs rendered in the editor panel.
     * Layout section IDs are injected at their structural positions on load:
     *   - header zone key(s) prepended at the front
     *   - footer zone key(s) appended at the back
     * Page sections occupy the middle and are freely reorderable.
     * On save, layout IDs are stripped back out before sending to the API.
     */
    order: string[];
    meta?: PageMeta;
    /** Kept for internal round-trip; not used directly by editor UI. */
    layout?: PageLayout;
}

/**
 * A single zone within the page layout.
 */
export interface PageZone {
    sections: Record<string, SectionInstance>;
    order: string[];
}

/**
 * Per-page layout configuration from `pages/{slug}.json → layout`.
 * Mirrors the backend zone-based structure.
 */
export interface PageLayout {
    /** Layout template type (e.g. "page"). */
    type?: string;
    /** Sections that appear BEFORE @yield('content') in the Blade layout. */
    header: PageZone;
    /** Sections that appear AFTER @yield('content') in the Blade layout. */
    footer: PageZone;
}

export interface PageMeta {
    title?: string;
    meta_title?: string;
    meta_description?: string;
    meta_keywords?: string;
}

export interface ThemeSettingsGroup {
    name: string;
    settings: SettingSchema[];
}

export interface ThemeSettingsData {
    schema: ThemeSettingsGroup[];
    values: Record<string, any>;
}

export interface SectionInstance {
    type: string;
    /** Custom label overriding the schema name — set by "Rename" in the editor. */
    _name?: string;
    settings: Record<string, any>;
    blocks: Record<string, BlockInstance>;
    order: string[];
    disabled?: boolean;
    /**
     * True when this section is a layout section (header, footer, …).
     * Layout sections are structural — they are not reorderable, not
     * duplicatable, and not removable from the editor.
     * The section's map key (e.g. "header") serves as the position slug.
     */
    layout?: boolean;
    /**
     * Which zone this layout section belongs to ("header" | "footer").
     * Set on load; stripped before saving back to the API.
     * Only present when `layout === true`.
     */
    layoutZone?: "header" | "footer";
}

export interface BlockInstance {
    type: string;
    /** Custom label overriding the schema name — set by "Rename" in the editor. */
    _name?: string;
    settings: Record<string, any>;
    blocks?: Record<string, BlockInstance>;
    order?: string[];
    disabled?: boolean;
}

export interface SectionData {
    type: string;
    view: string;
    schema: SectionSchema;
}

export interface SectionSchema {
    name: string;
    settings: SettingSchema[];
    blocks: BlockSchema[];
    presets?: any[];
}

export interface BlockSchema {
    type: string;
    name: string;
    settings: SettingSchema[];
    blocks?: { type: string; name: string }[];
    presets?: any[];
    /**
     * True when this schema comes from an inline local block definition
     * (has keys beyond `type` in the parent section's blocks array).
     * Local blocks have no registered Blade view and cannot be previewed
     * via api.renderBlock().
     */
    local?: boolean;
}

export interface BlockData {
    type: string;
    view: string;
    schema: BlockSchema;
}

export interface SettingSchema {
    id: string;
    type: string;
    label: string;
    default?: any;
    placeholder?: string;
    info?: string;
    options?: { label: string; value: any }[];
    min?: number;
    max?: number;
    step?: number;
    unit?: string;
    content?: string;
}
