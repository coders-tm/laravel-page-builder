/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { memo, useState, useEffect, useRef, useCallback } from "react"
import { SettingSchema } from "@/types/page-builder"
import { Input } from "@/components/ui/input"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import BUNDLED_FONTS from "./google-fonts.json"

interface FontEntry {
  family: string
  category: string
}

type Category = "all" | "sans-serif" | "serif" | "display" | "handwriting" | "monospace"

const CATEGORIES: Category[] = ["all", "sans-serif", "serif", "display", "handwriting", "monospace"]

const CATEGORY_LABELS: Record<Category, string> = {
  all: "All",
  "sans-serif": "Sans",
  serif: "Serif",
  display: "Display",
  handwriting: "Script",
  monospace: "Mono",
}

/** Inject a lightweight Google Fonts <link> for preview (text= loads only glyphs needed) */
function loadFontInEditor(family: string, mode: "preview" | "full" = "preview"): void {
  if (!family) return
  const slug = family.replace(/\s+/g, "-").toLowerCase()
  const id = mode === "full" ? `gf-full-${slug}` : `gf-preview-${slug}`
  if (document.getElementById(id)) return
  const link = document.createElement("link")
  link.id = id
  link.rel = "stylesheet"
  if (mode === "full") {
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(
      family,
    )}:wght@400;500;600;700&display=swap`
  } else {
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(
      family,
    )}&text=${encodeURIComponent(family)}&display=swap`
  }
  document.head.appendChild(link)
}

interface GoogleFontFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

function GoogleFontField({ setting, value, onChange }: GoogleFontFieldProps) {
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState("")
  const [activeCategory, setActiveCategory] = useState<Category>("all")
  const [fonts, setFonts] = useState<FontEntry[]>(BUNDLED_FONTS as FontEntry[])
  const [loading, setLoading] = useState(false)
  const listRef = useRef<HTMLDivElement>(null)
  const observerRef = useRef<IntersectionObserver | null>(null)

  const apiKey = (setting as SettingSchema & { apiKey?: string }).apiKey

  // Load from Google Fonts API if apiKey is provided
  useEffect(() => {
    if (!apiKey) return

    setLoading(true)
    fetch(`https://www.googleapis.com/webfonts/v1/webfonts?key=${apiKey}&sort=popularity`)
      .then((res) => res.json())
      .then((data: { items?: Array<{ family: string; category: string }> }) => {
        if (Array.isArray(data.items)) {
          const loaded: FontEntry[] = data.items.map((item) => ({
            family: item.family,
            category: item.category,
          }))
          // Sort alphabetically
          loaded.sort((a, b) => a.family.localeCompare(b.family))
          setFonts(loaded)
        }
      })
      .catch(() => {
        // Fall back to bundled fonts on error
        setFonts(BUNDLED_FONTS as FontEntry[])
      })
      .finally(() => {
        setLoading(false)
      })
  }, [apiKey])

  // Load the selected font in full quality for the trigger button preview
  useEffect(() => {
    if (value) loadFontInEditor(value, "full")
  }, [value])

  // Set up IntersectionObserver to load font previews lazily
  useEffect(() => {
    if (!open) return

    observerRef.current = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const family = (entry.target as HTMLElement).dataset.family
            if (family) {
              loadFontInEditor(family, "preview")
            }
          }
        })
      },
      { root: listRef.current, threshold: 0.1 },
    )

    return () => {
      observerRef.current?.disconnect()
      observerRef.current = null
    }
  }, [open])

  // Observe all font rows whenever the filtered list renders
  const observeItems = useCallback(() => {
    const observer = observerRef.current
    if (!observer || !listRef.current) return

    observer.disconnect()
    const items = listRef.current.querySelectorAll<HTMLElement>("[data-family]")
    items.forEach((el) => observer.observe(el))
  }, [])

  const filtered = fonts.filter((font) => {
    const matchesCategory = activeCategory === "all" || font.category === activeCategory
    const matchesSearch = !search.trim() || font.family.toLowerCase().includes(search.toLowerCase())
    return matchesCategory && matchesSearch
  })

  // Re-observe after filter changes
  useEffect(() => {
    if (open) {
      // Defer to let React flush the DOM update
      const id = requestAnimationFrame(observeItems)
      return () => cancelAnimationFrame(id)
    }
  }, [open, filtered.length, activeCategory, search, observeItems])

  const handleSelect = useCallback(
    (family: string) => {
      loadFontInEditor(family, "full")
      onChange(family)
      setOpen(false)
      setSearch("")
    },
    [onChange],
  )

  const handleOpenChange = useCallback((next: boolean) => {
    setOpen(next)
    if (!next) setSearch("")
  }, [])

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      <PopoverTrigger asChild>
        <button
          type="button"
          className="flex h-9 w-full items-center justify-between rounded-md border border-gray-200 bg-white px-3 text-sm transition-colors hover:border-gray-300 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
        >
          <span
            style={value ? { fontFamily: `"${value}", sans-serif` } : {}}
            className="truncate text-gray-800"
          >
            {value || (
              <span className="text-gray-400">{setting.placeholder || "Select a font"}</span>
            )}
          </span>
          <svg
            className="ml-2 h-3.5 w-3.5 shrink-0 text-gray-400"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
          >
            <path
              fillRule="evenodd"
              d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z"
              clipRule="evenodd"
            />
          </svg>
        </button>
      </PopoverTrigger>

      <PopoverContent
        className="p-0"
        style={{ width: "var(--radix-popover-trigger-width)" }}
        align="start"
      >
        {/* Category filter pills */}
        <div className="flex flex-wrap gap-1 px-2 pt-2 pb-1">
          {CATEGORIES.map((cat) => (
            <button
              key={cat}
              type="button"
              onClick={() => setActiveCategory(cat)}
              className={`rounded-full px-2 py-0.5 text-xs font-medium transition-colors ${
                activeCategory === cat
                  ? "bg-blue-600 text-white"
                  : "bg-gray-100 text-gray-600 hover:bg-gray-200"
              }`}
            >
              {CATEGORY_LABELS[cat]}
            </button>
          ))}
        </div>

        {/* Search input */}
        <div className="border-b border-gray-100 px-2 pb-2">
          <Input
            placeholder="Search fonts…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="h-8 text-xs"
            autoFocus
          />
        </div>

        {/* Font list */}
        <div ref={listRef} className="sidebar-scroll max-h-60 overflow-y-auto py-1">
          {loading && <p className="px-3 py-2 text-xs text-gray-400 italic">Loading fonts…</p>}

          {!loading && filtered.length === 0 && (
            <p className="px-3 py-2 text-xs text-gray-400 italic">No fonts found</p>
          )}

          {!loading &&
            filtered.map((font) => {
              const selected = value === font.family
              return (
                <button
                  key={font.family}
                  type="button"
                  data-family={font.family}
                  onClick={() => handleSelect(font.family)}
                  className={`flex w-full items-center justify-between px-3 py-1.5 text-left text-sm transition-colors hover:bg-gray-50 ${
                    selected ? "bg-blue-50 text-blue-700" : "text-gray-800"
                  }`}
                >
                  <span
                    style={{
                      fontFamily: `"${font.family}", sans-serif`,
                    }}
                  >
                    {font.family}
                  </span>
                  {selected && (
                    <svg
                      className="h-3.5 w-3.5 shrink-0 text-blue-600"
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 20 20"
                      fill="currentColor"
                    >
                      <path
                        fillRule="evenodd"
                        d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z"
                        clipRule="evenodd"
                      />
                    </svg>
                  )}
                </button>
              )
            })}
        </div>
      </PopoverContent>
    </Popover>
  )
}

export default memo(GoogleFontField)
