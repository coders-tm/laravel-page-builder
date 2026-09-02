/**
 * Core editor module — barrel export.
 *
 * Usage:
 *   import { Editor, EventBus } from '@/core/editor';
 */
export { Editor } from "./Editor"
export { EventBus } from "./EventBus"
export { SectionManager } from "./SectionManager"
export { BlockManager } from "./BlockManager"
export { SelectionManager } from "./SelectionManager"
export { LayoutManager } from "./LayoutManager"
export { ConfigManager } from "./ConfigManager"
export { NavigationManager } from "./NavigationManager"
export { BootstrapManager } from "./BootstrapManager"
export { ShortcutManager } from "./ShortcutManager"
export { InteractionManager } from "./InteractionManager"
export { HistoryManager } from "./HistoryManager"
export { PreviewManager } from "./PreviewManager"
export { PageManager } from "./PageManager"
export type { SidebarTab } from "./LayoutManager"
export type { EditorEventMap } from "./EditorEvents"
