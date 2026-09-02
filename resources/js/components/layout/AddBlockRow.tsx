import React from "react"
import { Plus } from "lucide-react"

/* ── "Add block" inline row ──────────────────────────────────────────── */

interface AddBlockRowProps {
  depth: number
  onAdd: () => void
  disabled?: boolean
}

export default function AddBlockRow({ depth, onAdd, disabled = false }: AddBlockRowProps) {
  return (
    <div
      onClick={disabled ? undefined : onAdd}
      className={`flex items-center gap-1.5 py-1.5 transition-colors select-none ${
        disabled ? "cursor-not-allowed opacity-40" : "group cursor-pointer"
      }`}
      style={{ paddingLeft: `${depth * 16 + 12}px`, paddingRight: 12 }}
      title={disabled ? "Block limit reached" : undefined}
    >
      <Plus
        size={12}
        strokeWidth={2.5}
        className={`shrink-0 transition-colors ${
          disabled ? "text-gray-400" : "text-gray-400 group-hover:text-blue-500"
        }`}
      />
      <span
        className={`text-[11px] font-medium transition-colors ${
          disabled ? "text-gray-400" : "text-gray-400 group-hover:text-blue-500"
        }`}
      >
        Add block
      </span>
    </div>
  )
}
