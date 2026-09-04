/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"
import { inputCls } from "./TextField"
import { cn } from "@/lib/utils"

interface TextareaFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
  mono?: boolean
}

function TextareaField({ setting, value, onChange, mono = false }: TextareaFieldProps) {
  return (
    <textarea
      className={cn(
        inputCls,
        "min-h-[80px] resize-y",
        mono && "font-mono text-[11px] leading-relaxed",
      )}
      value={value}
      placeholder={setting.placeholder || ""}
      onChange={(e) => onChange(e.target.value)}
    />
  )
}

export default memo(TextareaField)
