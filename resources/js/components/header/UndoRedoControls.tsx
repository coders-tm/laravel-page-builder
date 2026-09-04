/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React from "react"
import { Undo2, Redo2 } from "lucide-react"
import { cn } from "@/lib/utils"

interface UndoRedoControlsProps {
  canUndo: boolean
  canRedo: boolean
  onUndo: () => void
  onRedo: () => void
}

/**
 * Undo / Redo button pair.
 *
 * SRP: renders two icon buttons and delegates click events upward.
 * Knows nothing about history state management.
 */
export default function UndoRedoControls({
  canUndo,
  canRedo,
  onUndo,
  onRedo,
}: UndoRedoControlsProps) {
  const baseBtn =
    "flex items-center justify-center w-8 h-8 rounded-lg transition-colors duration-150"
  const enabledBtn = "text-gray-600 hover:text-gray-900 hover:bg-gray-100"
  const disabledBtn = "text-gray-300 cursor-not-allowed"

  return (
    <div className="flex items-center gap-0.5">
      <button
        type="button"
        title="Undo"
        disabled={!canUndo}
        onClick={onUndo}
        className={cn(baseBtn, canUndo ? enabledBtn : disabledBtn)}
      >
        <Undo2 className="h-[18px] w-[18px]" />
      </button>
      <button
        type="button"
        title="Redo"
        disabled={!canRedo}
        onClick={onRedo}
        className={cn(baseBtn, canRedo ? enabledBtn : disabledBtn)}
      >
        <Redo2 className="h-[18px] w-[18px]" />
      </button>
    </div>
  )
}
