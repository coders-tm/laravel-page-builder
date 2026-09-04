/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import type { EventBus } from "./EventBus"

/**
 * HistoryManager — undo/redo stack for the editor.
 *
 * Stores serialised page snapshots so the full page state
 * can be restored on undo/redo. Emits events so the preview
 * can re-render accordingly.
 *
 * @example
 * editor.history.snapshot();
 * editor.history.undo();
 * editor.history.redo();
 */

const MAX_HISTORY = 50

function serialise(state: unknown): string {
  return JSON.stringify(state)
}

export class HistoryManager {
  private past: string[] = []
  private future: string[] = []
  private lastPushed: string | null = null

  /**
   * Guard flag: when true, the next `maybeSnapshot()` call
   * is skipped because the state change was triggered by an
   * undo/redo restore, not a user edit.
   */
  private isRestoring = false

  /** Reactive version counter — bumped after every mutation. */
  private version = 0
  private versionListeners = new Set<() => void>()

  constructor(private events: EventBus) {}

  /* ── Reactive integration ────────────────────────────────────────── */

  /** Get a monotonically increasing version (useful for React re-renders). */
  getVersion(): number {
    return this.version
  }

  /** Subscribe to version changes (for React useSyncExternalStore). */
  subscribe(listener: () => void): () => void {
    this.versionListeners.add(listener)
    return () => {
      this.versionListeners.delete(listener)
    }
  }

  private bump(): void {
    this.version++
    for (const listener of this.versionListeners) {
      listener()
    }
  }

  /* ── Public API ──────────────────────────────────────────────────── */

  get canUndo(): boolean {
    return this.past.length >= 2
  }

  get canRedo(): boolean {
    return this.future.length > 0
  }

  /**
   * Record a new state snapshot.
   * Should be called after every user-initiated mutation.
   */
  push(state: any): void {
    const snap = serialise(state)
    if (snap === this.lastPushed) return

    this.past = [...this.past.slice(-(MAX_HISTORY - 1)), snap]
    this.future = []
    this.lastPushed = snap
    this.bump()
    this.events.emit("history:snapshot")
  }

  /**
   * Step backward one snapshot.
   * Returns the restored state, or null if nothing to undo.
   */
  undo(): any | null {
    if (this.past.length < 2) return null

    const current = this.past[this.past.length - 1]
    const previous = this.past[this.past.length - 2]

    this.past = this.past.slice(0, -1)
    this.future = [current, ...this.future]
    this.lastPushed = previous
    this.isRestoring = true

    this.bump()
    this.events.emit("history:undo")
    return JSON.parse(previous)
  }

  /**
   * Step forward one snapshot (re-apply an undone change).
   * Returns the restored state, or null if nothing to redo.
   */
  redo(): any | null {
    if (this.future.length === 0) return null

    const next = this.future[0]

    this.past = [...this.past, next]
    this.future = this.future.slice(1)
    this.lastPushed = next
    this.isRestoring = true

    this.bump()
    this.events.emit("history:redo")
    return JSON.parse(next)
  }

  /**
   * Should be called inside a `useEffect` watching `currentPage`.
   * Returns true if a snapshot was recorded, false if skipped
   * (due to an active undo/redo restore).
   */
  maybeSnapshot(state: any): boolean {
    if (this.isRestoring) {
      this.isRestoring = false
      return false
    }
    this.push(state)
    return true
  }

  /** Reset all history (e.g. when switching pages). */
  reset(initialState?: any): void {
    if (initialState !== undefined) {
      const snap = serialise(initialState)
      this.past = [snap]
      this.lastPushed = snap
    } else {
      this.past = []
      this.lastPushed = null
    }
    this.future = []
    this.bump()
    this.events.emit("history:reset")
  }
}
