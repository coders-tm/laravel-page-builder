/**
 * DrawerManager — centralized mobile bottom-drawer state.
 *
 * Tracks which panel (if any) is currently open in the mobile drawer.
 * Follows the same subscribe/notify pattern as LayoutManager so React
 * components can subscribe via useSyncExternalStore.
 *
 * This class is exported as a module-level singleton (`drawerManager`) so
 * any component can react to drawer state without prop drilling.
 */

export type MobileDrawerPanel = "sections" | "page" | "theme"

interface DrawerState {
  activePanel: MobileDrawerPanel | null
}

export class DrawerManager {
  private state: DrawerState = { activePanel: null }
  private listeners = new Set<() => void>()
  private version = 0

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

  get activePanel(): MobileDrawerPanel | null {
    return this.state.activePanel
  }

  get isOpen(): boolean {
    return this.state.activePanel !== null
  }

  /** Open a specific panel. No-op if it is already active. */
  open(panel: MobileDrawerPanel): void {
    if (this.state.activePanel === panel) return
    this.state = { activePanel: panel }
    this.notify()
  }

  /** Close the drawer. No-op if already closed. */
  close(): void {
    if (this.state.activePanel === null) return
    this.state = { activePanel: null }
    this.notify()
  }

  /** Toggle a panel open/closed. Opens if different panel is active. */
  toggle(panel: MobileDrawerPanel): void {
    if (this.state.activePanel === panel) {
      this.close()
    } else {
      this.open(panel)
    }
  }

  getSnapshot() {
    return {
      activePanel: this.activePanel,
      isOpen: this.isOpen,
    }
  }
}

/** Shared singleton — import this wherever you need drawer state. */
export const drawerManager = new DrawerManager()
