import { useState, useCallback, useRef } from "react"

/**
 * Maximum number of snapshots stored in the undo/redo stack.
 * Prevents unbounded memory growth.
 */
const MAX_HISTORY = 50

/**
 * Serialises a page state into a comparable snapshot string.
 * Used to avoid pushing duplicate consecutive states.
 */
function serialise(state: unknown): string {
  return JSON.stringify(state)
}

/**
 * Generic undo/redo history manager.
 *
 * Follows the Single Responsibility Principle — this hook is only concerned
 * with maintaining an ordered stack of snapshots and navigating between them.
 *
 * Uses refs for the history stacks to avoid React batching issues when
 * undo/redo need to return a value synchronously.
 *
 * Usage:
 *   const history = useHistory<PageData>();
 *   history.push(currentPage);   // after every meaningful mutation
 *   history.undo();              // step backward  → returns previous state
 *   history.redo();              // step forward   → returns next state
 */
export function useHistory<T>() {
  /**
   * We use refs for the stacks so undo/redo can read and mutate them
   * synchronously without React's batching delays. A version counter
   * forces re-renders so `canUndo` / `canRedo` stay reactive.
   */
  const pastRef = useRef<string[]>([])
  const futureRef = useRef<string[]>([])
  const lastPushed = useRef<string | null>(null)

  /** Bumped to force a re-render after stack mutations. */
  const [, setVersion] = useState(0)
  const bump = useCallback(() => setVersion((v) => v + 1), [])

  /**
   * Record a new state snapshot.
   *
   * Should be called *after* every user-initiated mutation
   * (setting change, section add/remove, block reorder, etc.).
   */
  const push = useCallback(
    (state: T) => {
      const snap = serialise(state)

      // Skip if the state hasn't actually changed.
      if (snap === lastPushed.current) return

      pastRef.current = [...pastRef.current.slice(-(MAX_HISTORY - 1)), snap]

      // Any new mutation invalidates the redo stack.
      futureRef.current = []
      lastPushed.current = snap
      bump()
    },
    [bump],
  )

  /**
   * Step backward one snapshot.
   * Returns the restored state, or `null` if nothing to undo.
   */
  const undo = useCallback((): T | null => {
    const past = pastRef.current
    if (past.length < 2) return null // need at least [previous, current]

    const current = past[past.length - 1]
    const previous = past[past.length - 2]

    // Move current onto the redo (future) stack.
    pastRef.current = past.slice(0, -1)
    futureRef.current = [current, ...futureRef.current]
    lastPushed.current = previous

    bump()
    return JSON.parse(previous)
  }, [bump])

  /**
   * Step forward one snapshot (re-apply an undone change).
   * Returns the restored state, or `null` if nothing to redo.
   */
  const redo = useCallback((): T | null => {
    const future = futureRef.current
    if (future.length === 0) return null

    const next = future[0]

    // Push it back onto the past stack.
    pastRef.current = [...pastRef.current, next]
    futureRef.current = future.slice(1)
    lastPushed.current = next

    bump()
    return JSON.parse(next)
  }, [bump])

  /** Reset all history (e.g. when switching pages). */
  const reset = useCallback(
    (initialState?: T) => {
      if (initialState !== undefined) {
        const snap = serialise(initialState)
        pastRef.current = [snap]
        lastPushed.current = snap
      } else {
        pastRef.current = []
        lastPushed.current = null
      }
      futureRef.current = []
      bump()
    },
    [bump],
  )

  return {
    /** Whether an undo operation is available. */
    canUndo: pastRef.current.length >= 2,
    /** Whether a redo operation is available. */
    canRedo: futureRef.current.length > 0,
    push,
    undo,
    redo,
    reset,
  }
}
