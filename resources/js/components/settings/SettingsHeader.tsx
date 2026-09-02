import React, { memo, useState, useRef, useEffect, useCallback } from "react"
import { ChevronLeft, Check, X } from "lucide-react"
import { cn } from "@/lib/utils"

interface SettingsHeaderProps {
  title: string
  /** Placeholder shown in the rename input (usually the schema name). */
  titlePlaceholder?: string
  /** Always-visible back arrow (sections never hide it; blocks always show it). */
  showBack?: boolean
  onBack: () => void
  /** Optional actions slot rendered on the right of the title. */
  actions?: React.ReactNode
  /** When provided, the title becomes click-to-rename. */
  onRename?: (name: string) => void
}

function SettingsHeader({
  title,
  titlePlaceholder,
  showBack = true,
  onBack,
  actions,
  onRename,
}: SettingsHeaderProps) {
  const [editing, setEditing] = useState(false)
  const [draft, setDraft] = useState(title)
  const inputRef = useRef<HTMLInputElement>(null)

  // Keep draft in sync when title changes externally
  useEffect(() => {
    if (!editing) setDraft(title)
  }, [title, editing])

  // Auto-focus + select-all on entering edit mode
  useEffect(() => {
    if (editing && inputRef.current) {
      inputRef.current.focus()
      inputRef.current.select()
    }
  }, [editing])

  const startEdit = useCallback(() => {
    setDraft(title)
    setEditing(true)
  }, [title])

  const commit = useCallback(() => {
    setEditing(false)
    onRename?.(draft.trim())
  }, [draft, onRename])

  const cancel = useCallback(() => {
    setEditing(false)
    setDraft(title)
  }, [title])

  const handleKeyDown = useCallback(
    (e: React.KeyboardEvent<HTMLInputElement>) => {
      if (e.key === "Enter") {
        e.preventDefault()
        commit()
      } else if (e.key === "Escape") {
        e.preventDefault()
        cancel()
      }
    },
    [commit, cancel],
  )

  return (
    <div className="shrink-0 border-b border-gray-200 px-4 py-3">
      <div className="flex items-center gap-2">
        {showBack && (
          <button
            onClick={onBack}
            className="flex shrink-0 items-center justify-center text-blue-600 transition-colors hover:text-blue-800"
            title="Back"
          >
            <ChevronLeft size={16} />
          </button>
        )}

        {editing ? (
          <>
            <input
              ref={inputRef}
              value={draft}
              onChange={(e) => setDraft(e.target.value)}
              onKeyDown={handleKeyDown}
              onBlur={commit}
              placeholder={titlePlaceholder ?? title}
              className="min-w-0 flex-1 border-b border-blue-400 bg-transparent text-sm font-bold text-gray-800 focus:border-blue-500 focus:outline-none"
            />
            <button
              onMouseDown={(e) => {
                e.preventDefault() // prevent blur before click
                commit()
              }}
              className="shrink-0 rounded p-1 text-blue-500 transition-colors hover:bg-blue-50"
              title="Save name"
            >
              <Check size={13} />
            </button>
            <button
              onMouseDown={(e) => {
                e.preventDefault()
                cancel()
              }}
              className="shrink-0 rounded p-1 text-gray-400 transition-colors hover:bg-gray-100"
              title="Cancel"
            >
              <X size={13} />
            </button>
          </>
        ) : (
          <>
            <h2
              className={cn(
                "flex-1 truncate text-sm font-bold text-gray-800",
                onRename && "cursor-text transition-colors hover:text-blue-600",
              )}
              onClick={onRename ? startEdit : undefined}
              title={onRename ? "Click to rename" : undefined}
            >
              {title}
            </h2>

            {actions && <div className="flex shrink-0 items-center gap-1">{actions}</div>}
          </>
        )}
      </div>
    </div>
  )
}

export default memo(SettingsHeader)
