/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
interface ShortcutActions {
  onUndo: () => void
  onRedo: () => void
  onInspectorToggle: () => void
}

/**
 * ShortcutManager — class-based keyboard shortcut handling.
 */
export class ShortcutManager {
  private actions: ShortcutActions = {
    onUndo: () => {},
    onRedo: () => {},
    onInspectorToggle: () => {},
  }

  private mounted = false

  configure(actions: Partial<ShortcutActions>): void {
    this.actions = {
      ...this.actions,
      ...actions,
    }
  }

  private readonly handleKeyDown = (e: KeyboardEvent): void => {
    const isMod = e.metaKey || e.ctrlKey
    if (!isMod) return

    // Let the browser handle undo/redo natively inside text fields
    if (e.key === "z" || e.key === "y") {
      const active = document.activeElement as HTMLElement | null
      if (active) {
        const tag = active.tagName.toLowerCase()
        const isTextInput = tag === "input" || tag === "textarea" || active.isContentEditable
        if (isTextInput) return
      }
    }

    if (e.key === "z" && !e.shiftKey) {
      e.preventDefault()
      this.actions.onUndo()
      return
    }

    if ((e.key === "z" && e.shiftKey) || e.key === "y") {
      e.preventDefault()
      this.actions.onRedo()
      return
    }

    if (e.key === "i") {
      e.preventDefault()
      this.actions.onInspectorToggle()
    }
  }

  mount(): void {
    if (this.mounted || typeof window === "undefined") return
    window.addEventListener("keydown", this.handleKeyDown)
    this.mounted = true
  }

  unmount(): void {
    if (!this.mounted || typeof window === "undefined") return
    window.removeEventListener("keydown", this.handleKeyDown)
    this.mounted = false
  }
}
