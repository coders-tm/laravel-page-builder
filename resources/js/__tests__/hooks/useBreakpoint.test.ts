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
import { useBreakpoint } from "@/hooks/useBreakpoint"

describe("useBreakpoint", () => {
  function mockMatchMedia(matches: boolean) {
    const listeners: Array<(e: MediaQueryListEvent) => void> = []
    const mq: MediaQueryList = {
      matches,
      media: "",
      onchange: null,
      addEventListener: vi.fn((_, fn) => listeners.push(fn as any)),
      removeEventListener: vi.fn(),
      addListener: vi.fn(),
      removeListener: vi.fn(),
      dispatchEvent: vi.fn(),
    }
    ;(window as any).__mqListeners = listeners
    window.matchMedia = vi.fn(() => mq)
    return mq
  }

  it("returns true when the media query matches", () => {
    mockMatchMedia(true)
    const { result } = renderHook(() => useBreakpoint(768))
    expect(result.current).toBe(true)
  })

  it("returns false when the media query does not match", () => {
    mockMatchMedia(false)
    const { result } = renderHook(() => useBreakpoint(768))
    expect(result.current).toBe(false)
  })

  it("updates reactively when the media query change event fires", () => {
    const mq = mockMatchMedia(false)
    const { result } = renderHook(() => useBreakpoint(768))
    expect(result.current).toBe(false)

    act(() => {
      // Simulate the media query changing to matching
      const listeners: any[] = (window as any).__mqListeners ?? []
      listeners.forEach((fn) => fn({ matches: true } as MediaQueryListEvent))
    })

    expect(result.current).toBe(true)
  })
})
