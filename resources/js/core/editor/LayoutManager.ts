import { useStore } from "@/core/store/useStore"
import type { BlockSchema } from "@/types/page-builder"
import type { EventBus } from "./EventBus"

export type SidebarTab = "sections" | "page" | "theme"

interface AddSectionModalState {
  isOpen: boolean
  insertIndex: number | null
}

interface AddBlockModalState {
  isOpen: boolean
  blockTypes: BlockSchema[]
  sectionId: string | null
  parentPath: string[]
  afterBlockId: string | null
}

interface LayoutState {
  sidebarTab: SidebarTab
  inspectorEnabled: boolean
  isDraggingLayout: boolean
  device: string
  viewportWidth: number
  addSectionModal: AddSectionModalState
  addBlockModal: AddBlockModalState
}

/**
 * LayoutManager — centralized UI layout state owned by the Editor.
 *
 * This keeps layout/editor flags out of component prop chains and exposes
 * a subscribable snapshot API for React integration.
 */
export class LayoutManager {
  private state: LayoutState = {
    sidebarTab: "sections",
    inspectorEnabled: true,
    isDraggingLayout: false,
    device: "desktop",
    viewportWidth: typeof window !== "undefined" ? window.innerWidth : 0,
    addSectionModal: {
      isOpen: false,
      insertIndex: null,
    },
    addBlockModal: {
      isOpen: false,
      blockTypes: [],
      sectionId: null,
      parentPath: [],
      afterBlockId: null,
    },
  }

  private listeners = new Set<() => void>()
  private version = 0

  constructor(private events: EventBus) {}

  subscribe(listener: () => void): () => void {
    this.listeners.add(listener)
    return () => {
      this.listeners.delete(listener)
    }
  }

  getVersion(): number {
    return this.version
  }

  private notify(): void {
    this.version += 1
    for (const listener of this.listeners) {
      listener()
    }
  }

  private setState(next: Partial<LayoutState>): void {
    this.state = { ...this.state, ...next }
    this.notify()
  }

  get sidebarTab(): SidebarTab {
    return this.state.sidebarTab
  }

  get inspectorEnabled(): boolean {
    return this.state.inspectorEnabled
  }

  get isDraggingLayout(): boolean {
    return this.state.isDraggingLayout
  }

  get device(): string {
    return this.state.device
  }

  get isFullscreen(): boolean {
    return this.state.device === "fullscreen"
  }

  get isDualSidebar(): boolean {
    return this.state.viewportWidth >= 1549 && !this.isFullscreen
  }

  /** Right settings sidebar is active in dual-sidebar mode. */
  get isRightSidebar(): boolean {
    return this.isDualSidebar
  }

  get addSectionModal(): AddSectionModalState {
    return this.state.addSectionModal
  }

  get addBlockModal(): AddBlockModalState {
    return this.state.addBlockModal
  }

  getSnapshot() {
    return {
      sidebarTab: this.sidebarTab,
      inspectorEnabled: this.inspectorEnabled,
      isDraggingLayout: this.isDraggingLayout,
      device: this.device,
      isFullscreen: this.isFullscreen,
      isDualSidebar: this.isDualSidebar,
      isRightSidebar: this.isRightSidebar,
      addSectionModal: this.addSectionModal,
      addBlockModal: this.addBlockModal,
    }
  }

  setSidebarTab(tab: SidebarTab): void {
    if (tab === this.state.sidebarTab) return
    this.setState({ sidebarTab: tab })
    this.events.emit("layout:sidebar-tab-changed", { tab })
  }

  setInspectorEnabled(enabled: boolean): void {
    if (enabled === this.state.inspectorEnabled) return
    this.setState({ inspectorEnabled: enabled })
    this.events.emit("layout:inspector-toggled", { enabled })
  }

  toggleInspector(): void {
    this.setInspectorEnabled(!this.state.inspectorEnabled)
  }

  startDrag(): void {
    if (this.state.isDraggingLayout) return
    this.setState({ isDraggingLayout: true })
  }

  endDrag(): void {
    if (!this.state.isDraggingLayout) return
    this.setState({ isDraggingLayout: false })
  }

  setDevice(device: string): void {
    if (device === this.state.device) return
    this.setState({ device })
    this.events.emit("layout:device-changed", { device })
  }

  setViewportWidth(width: number): void {
    if (!Number.isFinite(width)) return
    const next = Math.max(0, Math.floor(width))
    if (next === this.state.viewportWidth) return
    this.setState({ viewportWidth: next })
  }

  openAddSectionModal(position: string | null = null, targetId: string | null = null): void {
    let insertIndex: number | null = null

    if (targetId) {
      const order = useStore.getState().currentPage?.order ?? []
      const idx = order.indexOf(targetId)
      if (idx !== -1) {
        insertIndex = position === "after" ? idx + 1 : idx
      }
    }

    this.setState({
      addSectionModal: {
        isOpen: true,
        insertIndex,
      },
    })
  }

  closeAddSectionModal(): void {
    if (!this.state.addSectionModal.isOpen) return
    this.setState({
      addSectionModal: {
        isOpen: false,
        insertIndex: null,
      },
    })
  }

  openAddBlockModal(
    blockTypes: BlockSchema[],
    sectionId: string,
    parentPath: string[] = [],
    afterBlockId: string | null = null,
  ): void {
    if (!Array.isArray(blockTypes) || blockTypes.length === 0) return
    this.setState({
      addBlockModal: {
        isOpen: true,
        blockTypes,
        sectionId,
        parentPath: [...parentPath],
        afterBlockId,
      },
    })
  }

  closeAddBlockModal(): void {
    if (!this.state.addBlockModal.isOpen) return
    this.setState({
      addBlockModal: {
        isOpen: false,
        blockTypes: [],
        sectionId: null,
        parentPath: [],
        afterBlockId: null,
      },
    })
  }
}
