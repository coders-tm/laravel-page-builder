/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { memo } from "react"
import { AlignLeft, AlignCenter, AlignRight } from "lucide-react"
import { SettingSchema } from "@/types/page-builder"
import { cn } from "@/lib/utils"

interface AlignmentFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

const ALIGNMENTS = [
  { value: "left", Icon: AlignLeft },
  { value: "center", Icon: AlignCenter },
  { value: "right", Icon: AlignRight },
] as const

function AlignmentField({ value, onChange }: AlignmentFieldProps) {
  return (
    <div className="inline-flex overflow-hidden rounded-md border border-gray-200">
      {ALIGNMENTS.map(({ value: optValue, Icon }) => (
        <button
          key={optValue}
          onClick={() => onChange(optValue)}
          className={cn(
            "px-3 py-1.5 transition-colors",
            value === optValue
              ? "bg-blue-500 text-white"
              : "bg-white text-gray-500 hover:bg-gray-100",
          )}
          title={optValue.charAt(0).toUpperCase() + optValue.slice(1)}
        >
          <Icon size={16} />
        </button>
      ))}
    </div>
  )
}

export default memo(AlignmentField)
