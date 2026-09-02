import React, { memo, useState, useRef, useEffect } from "react"
import { CopyPlus, Copy, Eye, EyeOff, Trash2, MoreVertical } from "lucide-react"

interface SettingsActionsProps {
  isHidden: boolean
  onDuplicate?: () => void
  onHide: () => void
  onRemove?: () => void
  /** Section-only: remove label override (defaults to "Remove") */
  removeLabel?: string
  /** Section-only: remove button uses sm text; block uses xs */
  size?: "sm" | "xs"
}

function SettingsActions({
  isHidden,
  onDuplicate,
  onHide,
  onRemove,
  removeLabel = "Remove",
  size = "xs",
}: SettingsActionsProps) {
  const [open, setOpen] = useState(false)
  const ref = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!open) return
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) {
        setOpen(false)
      }
    }
    document.addEventListener("mousedown", handler)
    return () => document.removeEventListener("mousedown", handler)
  }, [open])

  const iconSize = size === "sm" ? 15 : 14
  const itemCls = `flex items-center gap-2.5 w-full px-3 py-2 text-left text-${size} font-medium text-gray-700 hover:bg-gray-100 transition-colors`

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen((v) => !v)}
        className="rounded p-1 text-gray-500 transition-colors hover:bg-gray-200 hover:text-gray-700"
        title="Actions"
      >
        <MoreVertical size={16} />
      </button>

      {open && (
        <div className="absolute top-full right-0 z-50 mt-1 w-44 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-2xl">
          {onDuplicate && (
            <button
              onClick={() => {
                onDuplicate()
                setOpen(false)
              }}
              className={itemCls}
            >
              {size === "sm" ? <CopyPlus size={iconSize} /> : <Copy size={iconSize} />}
              Duplicate
            </button>
          )}

          <button
            onClick={() => {
              onHide()
              setOpen(false)
            }}
            className={itemCls}
          >
            {isHidden ? <Eye size={iconSize} /> : <EyeOff size={iconSize} />}
            {isHidden ? "Show" : "Hide"}
          </button>

          {onRemove && (
            <>
              <div className="my-1 border-t border-gray-100" />

              <button
                onClick={() => {
                  onRemove()
                  setOpen(false)
                }}
                className={`flex w-full items-center gap-2.5 px-3 py-2 text-left text-${size} font-medium text-red-600 transition-colors hover:bg-red-50`}
              >
                <Trash2 size={iconSize} />
                {removeLabel}
              </button>
            </>
          )}
        </div>
      )}
    </div>
  )
}

export default memo(SettingsActions)
