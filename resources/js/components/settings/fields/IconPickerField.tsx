import React, { memo, useState, useMemo, useCallback, useRef, useEffect } from "react"
import { Search, X, AlertCircle, ChevronDown } from "lucide-react"
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome"
import type { IconDefinition } from "@fortawesome/fontawesome-svg-core"
import * as solidIcons from "@fortawesome/free-solid-svg-icons"
import * as regularIcons from "@fortawesome/free-regular-svg-icons"
import * as brandIcons from "@fortawesome/free-brands-svg-icons"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { cn } from "@/lib/utils"
import { inputCls } from "./TextField"

export type IconEntry = {
  label: string
  value: string // FA: "fas fa-house"  |  MD: "home"
  group?: string
}

export type IconVariant = "fa" | "md"

// ── FontAwesome icon lookup map ────────────────────────────────────────────
// Build once: "fas fa-house" → IconDefinition object
type AnyIconModule = Record<string, unknown>
function buildFaMap(...mods: AnyIconModule[]): Map<string, IconDefinition> {
  const map = new Map<string, IconDefinition>()
  mods.forEach((mod) => {
    Object.values(mod).forEach((icon) => {
      const ic = icon as IconDefinition
      if (ic && ic.iconName && ic.prefix && ic.icon) {
        const key = `${ic.prefix} fa-${ic.iconName}`
        if (!map.has(key)) map.set(key, ic)
      }
    })
  })
  return map
}
const FA_MAP = buildFaMap(
  solidIcons as AnyIconModule,
  regularIcons as AnyIconModule,
  brandIcons as AnyIconModule,
)

// ── Material Design Google Fonts injection ─────────────────────────────────
// Inject the font links once so ligature-based MD icons render in the editor.
function ensureMdFonts() {
  if (document.getElementById("pb-md-fonts")) return
  const link = document.createElement("link")
  link.id = "pb-md-fonts"
  link.rel = "stylesheet"
  link.href =
    "https://fonts.googleapis.com/icon?family=Material+Icons|Material+Icons+Outlined|Material+Icons+Round|Material+Icons+Sharp|Material+Icons+Two+Tone"
  document.head.appendChild(link)
}

// ── Icon preview renderer ──────────────────────────────────────────────────
function FaIconPreview({ value, className = "" }: { value: string; className?: string }) {
  const def = FA_MAP.get(value)
  if (!def) {
    return <AlertCircle className={cn("h-4 w-4 text-gray-300", className)} aria-hidden="true" />
  }
  return <FontAwesomeIcon icon={def} className={className} aria-hidden="true" />
}

function MdIconPreview({
  value,
  fontClass,
  sizeClass = "text-base",
}: {
  value: string
  fontClass: string
  sizeClass?: string
}) {
  useEffect(() => {
    ensureMdFonts()
  }, [])
  return (
    <span className={cn(fontClass, sizeClass, "leading-none select-none")} aria-hidden="true">
      {value}
    </span>
  )
}

/** Exported helper for field components to render a quick preview. */
export function renderIconPreview(
  value: string,
  variant: IconVariant,
  mdFontClass = "material-icons",
): React.ReactNode {
  if (!value) return null
  if (variant === "fa") return <FaIconPreview value={value} />
  return <MdIconPreview value={value} fontClass={mdFontClass} />
}

// ── Icon grid cell ─────────────────────────────────────────────────────────
const IconCell = memo(function IconCell({
  ic,
  isSelected,
  variant,
  onSelect,
}: {
  ic: IconEntry
  isSelected: boolean
  variant: IconVariant
  onSelect: (v: string) => void
}) {
  return (
    <button
      type="button"
      title={ic.label}
      onClick={() => onSelect(ic.value)}
      className={cn(
        "flex flex-col items-center justify-center gap-0.5 rounded-md px-1 py-2 transition-colors",
        isSelected ? "bg-blue-500 text-white" : "text-gray-600 hover:bg-gray-100",
      )}
    >
      <span className="flex h-5 w-5 items-center justify-center text-[15px] leading-none">
        {variant === "fa" ? (
          <FaIconPreview value={ic.value} />
        ) : (
          <MdIconPreview
            value={ic.value}
            fontClass={ic.group ?? "material-icons"}
            sizeClass="text-[18px]"
          />
        )}
      </span>
      <span className="w-full truncate text-center text-[8px] leading-tight opacity-60">
        {ic.label}
      </span>
    </button>
  )
})

// ── Pagination ─────────────────────────────────────────────────────────────
const PAGE_SIZE = 56 // 7 cols × 8 rows — fits at field width

// ── Main component ─────────────────────────────────────────────────────────

interface IconPickerFieldProps {
  value: string
  onChange: (val: string) => void
  icons: IconEntry[]
  variant: IconVariant
  placeholder?: string
  mdFontClass?: string
}

function IconPickerField({
  value,
  onChange,
  icons,
  variant,
  placeholder = "Select an icon…",
  mdFontClass = "material-icons",
}: IconPickerFieldProps) {
  const [open, setOpen] = useState(false)
  const [search, setSearch] = useState("")
  const [activeGroup, setActiveGroup] = useState<string | null>(null)
  const [page, setPage] = useState(0)
  const searchRef = useRef<HTMLInputElement>(null)

  // Inject MD fonts
  useEffect(() => {
    if (variant === "md") ensureMdFonts()
  }, [variant])

  // Focus search when popover opens
  useEffect(() => {
    if (open) setTimeout(() => searchRef.current?.focus(), 60)
  }, [open])

  // Groups from icon list
  const groups = useMemo<string[]>(() => {
    const seen = new Set<string>()
    icons.forEach((ic) => {
      if (ic.group) seen.add(ic.group)
    })
    return Array.from(seen)
  }, [icons])

  // Filtered icon list
  const filtered = useMemo(() => {
    const q = search.toLowerCase().trim()
    return icons.filter((ic) => {
      const matchSearch =
        !q || ic.label.toLowerCase().includes(q) || ic.value.toLowerCase().includes(q)
      const matchGroup = !activeGroup || ic.group === activeGroup
      return matchSearch && matchGroup
    })
  }, [icons, search, activeGroup])

  // Reset page when filter changes
  useEffect(() => {
    setPage(0)
  }, [filtered])

  const totalPages = Math.ceil(filtered.length / PAGE_SIZE)
  const visibleIcons = filtered.slice(page * PAGE_SIZE, (page + 1) * PAGE_SIZE)

  const handleSelect = useCallback(
    (iconValue: string) => {
      onChange(iconValue)
      setOpen(false)
      setSearch("")
      setPage(0)
    },
    [onChange],
  )

  const handleClear = useCallback(
    (e: React.MouseEvent) => {
      e.stopPropagation()
      onChange("")
    },
    [onChange],
  )

  const handleOpenChange = useCallback((next: boolean) => {
    setOpen(next)
    if (!next) {
      setSearch("")
      setPage(0)
    }
  }, [])

  // Current entry for trigger display
  const currentEntry = useMemo(() => icons.find((ic) => ic.value === value), [icons, value])
  const currentMdFont = currentEntry?.group ?? mdFontClass

  return (
    <Popover open={open} onOpenChange={handleOpenChange}>
      {/* ── Trigger ── */}
      <PopoverTrigger asChild>
        <button
          type="button"
          className={cn(inputCls, "flex w-full cursor-pointer items-center gap-2 text-left")}
        >
          {value ? (
            <>
              <span className="flex h-5 w-5 shrink-0 items-center justify-center text-gray-600">
                {variant === "fa" ? (
                  <FaIconPreview value={value} className="text-sm" />
                ) : (
                  <MdIconPreview value={value} fontClass={currentMdFont} sizeClass="text-[18px]" />
                )}
              </span>
              <span className="flex-1 truncate text-[13px] text-gray-700">
                {currentEntry?.label ?? value}
              </span>
              <span
                role="button"
                tabIndex={-1}
                onClick={handleClear}
                className="ml-auto shrink-0 rounded p-0.5 text-gray-400 hover:text-gray-600"
                aria-label="Clear selection"
              >
                <X className="h-3 w-3" />
              </span>
            </>
          ) : (
            <>
              <span className="flex-1 text-[13px] text-gray-400">{placeholder}</span>
              <ChevronDown className="h-3.5 w-3.5 shrink-0 text-gray-400" />
            </>
          )}
        </button>
      </PopoverTrigger>

      {/* ── Dropdown — width locked to trigger via CSS var ── */}
      <PopoverContent
        className="flex flex-col overflow-hidden p-0"
        style={{
          width: "var(--radix-popover-trigger-width)",
          maxHeight: "min(420px, var(--radix-popover-content-available-height))",
        }}
        align="start"
        sideOffset={4}
        onOpenAutoFocus={(e) => e.preventDefault()}
      >
        {/* Search */}
        <div className="shrink-0 border-b border-gray-100 px-2 pt-2 pb-1.5">
          <div className="relative">
            <Search className="pointer-events-none absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" />
            <input
              ref={searchRef}
              type="text"
              value={search}
              onChange={(e) => {
                setSearch(e.target.value)
                setActiveGroup(null)
              }}
              placeholder="Search icons…"
              className="h-7 w-full rounded-md border border-gray-200 bg-gray-50 pr-3 pl-8 text-[12px] focus:ring-1 focus:ring-blue-400 focus:outline-none"
            />
          </div>
        </div>

        {/* Group tabs */}
        {groups.length > 0 && (
          <div className="flex shrink-0 gap-1 overflow-x-auto border-b border-gray-100 px-2 py-1.5">
            <button
              type="button"
              onClick={() => {
                setActiveGroup(null)
                setPage(0)
              }}
              className={cn(
                "rounded-full px-2 py-0.5 text-[10px] font-medium whitespace-nowrap transition-colors",
                !activeGroup
                  ? "bg-blue-500 text-white"
                  : "bg-gray-100 text-gray-500 hover:bg-gray-200",
              )}
            >
              All
            </button>
            {groups.map((g) => {
              const label = g.startsWith("material-icons")
                ? g.replace("material-icons", "").replace(/-/g, " ").trim() || "Filled"
                : g
              return (
                <button
                  key={g}
                  type="button"
                  onClick={() => {
                    setActiveGroup((prev) => (prev === g ? null : g))
                    setPage(0)
                  }}
                  className={cn(
                    "rounded-full px-2 py-0.5 text-[10px] font-medium whitespace-nowrap capitalize transition-colors",
                    activeGroup === g
                      ? "bg-blue-500 text-white"
                      : "bg-gray-100 text-gray-500 hover:bg-gray-200",
                  )}
                >
                  {label}
                </button>
              )
            })}
          </div>
        )}

        {/* Result count + Clear */}
        <div className="flex shrink-0 items-center justify-between px-2 py-1">
          <span className="text-[10px] text-gray-400">
            {filtered.length.toLocaleString()} icon
            {filtered.length !== 1 ? "s" : ""}
            {search ? ` for "${search}"` : ""}
          </span>
          {value && (
            <button
              type="button"
              onClick={() => {
                onChange("")
                setOpen(false)
              }}
              className="text-[10px] text-gray-400 transition-colors hover:text-red-500"
            >
              Clear
            </button>
          )}
        </div>

        {/* Icon grid */}
        <div className="flex-1 overflow-y-auto px-1.5 pb-1.5">
          {filtered.length === 0 ? (
            <p className="py-6 text-center text-[11px] text-gray-400">No icons found</p>
          ) : (
            <div
              className="grid"
              style={{
                gridTemplateColumns: "repeat(7, minmax(0, 1fr))",
                gap: "1px",
              }}
            >
              {visibleIcons.map((ic) => (
                <IconCell
                  key={ic.value}
                  ic={ic}
                  isSelected={ic.value === value}
                  variant={variant}
                  onSelect={handleSelect}
                />
              ))}
            </div>
          )}
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="flex shrink-0 items-center justify-between border-t border-gray-100 bg-gray-50 px-2 py-1.5">
            <button
              type="button"
              disabled={page === 0}
              onClick={() => setPage((p) => Math.max(0, p - 1))}
              className="rounded border border-gray-200 bg-white px-2 py-0.5 text-[11px] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
            >
              ← Prev
            </button>
            <span className="text-[10px] font-medium text-gray-500 tabular-nums">
              {page + 1} / {totalPages}
            </span>
            <button
              type="button"
              disabled={page >= totalPages - 1}
              onClick={() => setPage((p) => Math.min(totalPages - 1, p + 1))}
              className="rounded border border-gray-200 bg-white px-2 py-0.5 text-[11px] hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40"
            >
              Next →
            </button>
          </div>
        )}
      </PopoverContent>
    </Popover>
  )
}

export default memo(IconPickerField)
