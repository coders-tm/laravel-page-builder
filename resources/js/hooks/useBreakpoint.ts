import { useState, useEffect } from "react"

/**
 * Small shared hook that subscribes to a media query and returns whether it matches.
 * Centralizes the event/listener logic so `useBreakpoint` and `useMaxBreakpoint`
 * can reuse the same implementation without duplication.
 */
const useMediaQuery = (query: string): boolean => {
  const [matches, setMatches] = useState<boolean>(() => {
    if (typeof window === "undefined") return false
    return window.matchMedia(query).matches
  })

  useEffect(() => {
    const mq = window.matchMedia(query)

    const handler = (e: MediaQueryListEvent) => setMatches(e.matches)

    // Modern browsers
    if (mq.addEventListener) {
      mq.addEventListener("change", handler)
    } else {
      // Safari < 14 fallback
      mq.addListener(handler)
    }

    // Sync on mount in case the value differs from the SSR default
    setMatches(mq.matches)

    return () => {
      if (mq.removeEventListener) {
        mq.removeEventListener("change", handler)
      } else {
        mq.removeListener(handler)
      }
    }
  }, [query])

  return matches
}

/**
 * Returns `true` when the viewport is wider than the given pixel threshold.
 * Defaults to 1280px (Tailwind's `xl` breakpoint).
 */
export function useBreakpoint(minWidth = 1280): boolean {
  return useMediaQuery(`(min-width: ${minWidth}px)`)
}

/**
 * Returns `true` when the viewport is narrower than or equal to the given pixel threshold.
 * Defaults to 1279px (the inclusive max for Tailwind's `xl` breakpoint).
 *
 * This lets components query the "max-width" side of the same breakpoint without
 * duplicating the media query subscription logic.
 */
export function useMaxBreakpoint(maxWidth = 1279): boolean {
  return useMediaQuery(`(max-width: ${maxWidth}px)`)
}
