/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest"
import { renderHook, act } from "@testing-library/react"
import { useDebounce } from "@/hooks/useDebounce"

/**
 * useDebounce returns a debounced *function*, not a debounced *value*.
 * It takes (fn, delay) and returns (...args) => void.
 */
describe("useDebounce", () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it("returns a function", () => {
    const fn = vi.fn()
    const { result } = renderHook(() => useDebounce(fn, 300))
    expect(typeof result.current).toBe("function")
  })

  it("does not call the original function before the delay elapses", () => {
    const fn = vi.fn()
    const { result } = renderHook(() => useDebounce(fn, 300))
    act(() => {
      result.current("hello")
    })
    expect(fn).not.toHaveBeenCalled()
  })

  it("calls the original function after the delay", () => {
    const fn = vi.fn()
    const { result } = renderHook(() => useDebounce(fn, 300))
    act(() => {
      result.current("hello")
      vi.advanceTimersByTime(300)
    })
    expect(fn).toHaveBeenCalledTimes(1)
    expect(fn).toHaveBeenCalledWith("hello")
  })

  it("coalesces rapid calls — only the last call fires", () => {
    const fn = vi.fn()
    const { result } = renderHook(() => useDebounce(fn, 300))
    act(() => {
      result.current("a")
      result.current("b")
      result.current("c")
      vi.advanceTimersByTime(300)
    })
    expect(fn).toHaveBeenCalledTimes(1)
    expect(fn).toHaveBeenCalledWith("c")
  })

  it("passes arguments through to the original function", () => {
    const fn = vi.fn()
    const { result } = renderHook(() => useDebounce(fn, 100))
    act(() => {
      result.current(1, 2, 3)
      vi.advanceTimersByTime(100)
    })
    expect(fn).toHaveBeenCalledWith(1, 2, 3)
  })

  it("does not call the function again if it was already called once", () => {
    const fn = vi.fn()
    const { result } = renderHook(() => useDebounce(fn, 200))
    act(() => {
      result.current("first")
      vi.advanceTimersByTime(200)
    })
    act(() => {
      vi.advanceTimersByTime(500)
    })
    expect(fn).toHaveBeenCalledTimes(1)
  })
})
