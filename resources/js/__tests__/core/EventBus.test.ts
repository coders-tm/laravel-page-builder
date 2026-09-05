/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach } from "vitest"
import { EventBus } from "@/core/editor/EventBus"

describe("EventBus", () => {
  let bus: EventBus

  beforeEach(() => {
    bus = new EventBus()
  })

  it("calls a listener when its event is emitted", () => {
    const fn = vi.fn()
    bus.on("test", fn)
    bus.emit("test", { value: 1 })
    expect(fn).toHaveBeenCalledTimes(1)
    expect(fn).toHaveBeenCalledWith({ value: 1 })
  })

  it("on() returns an unsubscribe function that removes the listener", () => {
    const fn = vi.fn()
    const off = bus.on("test", fn)
    off()
    bus.emit("test")
    expect(fn).not.toHaveBeenCalled()
  })

  it("does not call listeners for other events", () => {
    const fn = vi.fn()
    bus.on("a", fn)
    bus.emit("b")
    expect(fn).not.toHaveBeenCalled()
  })

  it("calls all listeners registered for the same event", () => {
    const fn1 = vi.fn()
    const fn2 = vi.fn()
    bus.on("test", fn1)
    bus.on("test", fn2)
    bus.emit("test")
    expect(fn1).toHaveBeenCalledTimes(1)
    expect(fn2).toHaveBeenCalledTimes(1)
  })

  it("once() fires the listener exactly once", () => {
    const fn = vi.fn()
    bus.once("test", fn)
    bus.emit("test")
    bus.emit("test")
    expect(fn).toHaveBeenCalledTimes(1)
  })

  it("once() passes arguments correctly on the single call", () => {
    const fn = vi.fn()
    bus.once("test", fn)
    bus.emit("test", "hello", 42)
    expect(fn).toHaveBeenCalledWith("hello", 42)
  })

  it("catches errors in a listener and logs them, then fires remaining listeners", () => {
    const errorSpy = vi.spyOn(console, "error").mockImplementation(() => {})
    const bad = vi.fn(() => {
      throw new Error("boom")
    })
    const good = vi.fn()
    bus.on("test", bad)
    bus.on("test", good)
    bus.emit("test")
    expect(errorSpy).toHaveBeenCalled()
    expect(good).toHaveBeenCalledTimes(1)
    errorSpy.mockRestore()
  })

  it("off() removes all listeners for a specific event", () => {
    const fn = vi.fn()
    bus.on("test", fn)
    bus.off("test")
    bus.emit("test")
    expect(fn).not.toHaveBeenCalled()
  })

  it("clear() removes all listeners across all events", () => {
    const fn1 = vi.fn()
    const fn2 = vi.fn()
    bus.on("a", fn1)
    bus.on("b", fn2)
    bus.clear()
    bus.emit("a")
    bus.emit("b")
    expect(fn1).not.toHaveBeenCalled()
    expect(fn2).not.toHaveBeenCalled()
  })

  it("emitting an event with no listeners does not throw", () => {
    expect(() => bus.emit("no-listeners")).not.toThrow()
  })
})
