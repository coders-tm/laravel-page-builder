/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { memo, useMemo } from "react"
import { SettingSchema } from "@/types/page-builder"
import { inputCls } from "./TextField"
import { cn } from "@/lib/utils"
import { hexToHsl, hslToHex } from "@/lib/colors"

interface ColorFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
  /** If true, renders as a background (accepts gradients) */
  isBackground?: boolean
}

function ColorField({ setting, value, onChange, isBackground = false }: ColorFieldProps) {
  const isHsl = setting.mode === "hsl"

  // For the <input type="color"> picker, we always need a hex value.
  const hexValue = useMemo(() => {
    if (!value) return "#000000"
    if (isHsl) {
      try {
        return hslToHex(value)
      } catch (e) {
        return "#000000"
      }
    }
    return /^#[0-9a-fA-F]{3,8}$/.test(value) ? value : "#000000"
  }, [value, isHsl])

  const handlePickerChange = (hex: string) => {
    if (isHsl) {
      onChange(hexToHsl(hex))
    } else {
      onChange(hex)
    }
  }

  if (isBackground) {
    return (
      <div className="flex items-center gap-2">
        <div
          className="h-9 w-9 shrink-0 rounded-lg border border-gray-200 shadow-sm transition-all hover:scale-105"
          style={{ background: value || "#000" }}
        />
        <input
          type="text"
          className={cn(inputCls, "h-9 flex-1 px-3 font-mono text-[11px]")}
          value={value}
          placeholder="linear-gradient(…) or #hex"
          onChange={(e) => onChange(e.target.value)}
        />
      </div>
    )
  }

  return (
    <div className="group flex items-center gap-2">
      <div className="relative h-9 w-9 shrink-0">
        <input
          type="color"
          className="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
          value={hexValue}
          onChange={(e) => handlePickerChange(e.target.value)}
        />
        <div
          className="group-hover:border-primary-400 h-full w-full rounded-lg border border-gray-200 p-1 shadow-sm transition-colors"
          style={{ backgroundColor: hexValue }}
        >
          <div className="h-full w-full rounded-[4px] border border-black/5" />
        </div>
      </div>
      <input
        type="text"
        className={cn(
          inputCls,
          "h-9 flex-1 bg-gray-50/50 px-3 font-mono text-[11px] transition-colors focus:bg-white",
        )}
        value={value}
        placeholder={isHsl ? "H S% L%" : "#hex"}
        onChange={(e) => onChange(e.target.value)}
      />
    </div>
  )
}

export default memo(ColorField)
