/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { HistoryManager } from "@/core/editor/HistoryManager"
import { EventBus } from "@/core/editor/EventBus"

describe("HistoryManager", () => {
  let history: HistoryManager
  let events: EventBus

  beforeEach(() => {
    events = new EventBus()
    history = new HistoryManager(events)
  })

  /* ── push ─────────────────────────────────────────────────────── */
  it("push() adds a snapshot and makes canUndo true after two entries", () => {
    expect(history.canUndo).toBe(false)
    history.push({ page: "a" })
    expect(history.canUndo).toBe(false) // needs ≥ 2
    history.push({ page: "b" })
    expect(history.canUndo).toBe(true)
  })

  it("push() deduplicates identical consecutive snapshots", () => {
    const state = { page: "a" }
    history.push(state)
    history.push(state)
    // still only one entry, so canUndo is false
    expect(history.canUndo).toBe(false)
  })

  it("push() clears the future stack on a new change", () => {
    history.push({ page: "a" })
    history.push({ page: "b" })
    history.undo()
    expect(history.canRedo).toBe(true)
    history.push({ page: "c" })
    expect(history.canRedo).toBe(false)
  })

  it("push() emits history:snapshot event", () => {
    const fn = vi.fn()
    events.on("history:snapshot", fn)
    history.push({ page: "x" })
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it("push() respects MAX_HISTORY cap of 50", () => {
    for (let i = 0; i < 55; i++) {
      history.push({ page: `state-${i}` })
    }
    // After 55 unique pushes the past array should be capped at 50
    // Verify by checking that undo steps exhaust before reaching state-0
    let count = 0
    while (history.canUndo) {
      history.undo()
      count++
    }
    expect(count).toBeLessThanOrEqual(49)
  })

  /* ── undo ─────────────────────────────────────────────────────── */
  it("undo() returns null when there is nothing to undo", () => {
    expect(history.undo()).toBeNull()
  })

  it("undo() returns the previous state and moves current to future", () => {
    history.push({ page: "a" })
    history.push({ page: "b" })
    const restored = history.undo()
    expect(restored).toEqual({ page: "a" })
    expect(history.canRedo).toBe(true)
  })

  it("undo() emits history:undo event", () => {
    const fn = vi.fn()
    events.on("history:undo", fn)
    history.push({ page: "a" })
    history.push({ page: "b" })
    history.undo()
    expect(fn).toHaveBeenCalledTimes(1)
  })

  /* ── redo ─────────────────────────────────────────────────────── */
  it("canRedo is false initially", () => {
    expect(history.canRedo).toBe(false)
  })

  it("redo() returns null when there is nothing to redo", () => {
    expect(history.redo()).toBeNull()
  })

  it("redo() re-applies an undone state", () => {
    history.push({ page: "a" })
    history.push({ page: "b" })
    history.undo()
    const redone = history.redo()
    expect(redone).toEqual({ page: "b" })
    expect(history.canRedo).toBe(false)
  })

  it("redo() emits history:redo event", () => {
    const fn = vi.fn()
    events.on("history:redo", fn)
    history.push({ page: "a" })
    history.push({ page: "b" })
    history.undo()
    history.redo()
    expect(fn).toHaveBeenCalledTimes(1)
  })

  /* ── maybeSnapshot ────────────────────────────────────────────── */
  it("maybeSnapshot() records a snapshot and returns true normally", () => {
    const result = history.maybeSnapshot({ page: "a" })
    expect(result).toBe(true)
    history.maybeSnapshot({ page: "b" })
    expect(history.canUndo).toBe(true)
  })

  it("maybeSnapshot() skips snapshot after undo (isRestoring guard)", () => {
    history.push({ page: "a" })
    history.push({ page: "b" })
    history.undo()
    // next maybeSnapshot should be skipped
    const result = history.maybeSnapshot({ page: "a" })
    expect(result).toBe(false)
  })

  /* ── reset ────────────────────────────────────────────────────── */
  it("reset() without initialState clears all history", () => {
    history.push({ page: "a" })
    history.push({ page: "b" })
    history.reset()
    expect(history.canUndo).toBe(false)
    expect(history.canRedo).toBe(false)
  })

  it("reset() with initialState primes the stack", () => {
    history.push({ page: "a" })
    history.push({ page: "b" })
    history.reset({ page: "initial" })
    // After reset with initial, canUndo should be false (only 1 entry)
    expect(history.canUndo).toBe(false)
    // But maybeSnapshot(same) should deduplicate
    history.maybeSnapshot({ page: "initial" })
    expect(history.canUndo).toBe(false)
  })

  it("reset() emits history:reset event", () => {
    const fn = vi.fn()
    events.on("history:reset", fn)
    history.reset()
    expect(fn).toHaveBeenCalledTimes(1)
  })

  /* ── subscribe / version ──────────────────────────────────────── */
  it("getVersion() increments on every mutation", () => {
    const v0 = history.getVersion()
    history.push({ page: "a" })
    expect(history.getVersion()).toBeGreaterThan(v0)
  })

  it("subscribe() notifies listeners on mutations", () => {
    const fn = vi.fn()
    history.subscribe(fn)
    history.push({ page: "a" })
    expect(fn).toHaveBeenCalled()
  })

  it("subscribe() returns an unsubscribe function", () => {
    const fn = vi.fn()
    const off = history.subscribe(fn)
    off()
    history.push({ page: "a" })
    expect(fn).not.toHaveBeenCalled()
  })
})
