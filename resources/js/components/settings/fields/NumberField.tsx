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
import { Input } from "@/components/ui/input"

interface NumberFieldProps {
  setting: SettingSchema
  value: number | string
  onChange: (val: number | string) => void
}

function NumberField({ setting, value, onChange }: NumberFieldProps) {
  return (
    <Input
      type="number"
      value={value}
      placeholder={setting.placeholder || ""}
      onChange={(e) => onChange(e.target.value === "" ? "" : Number(e.target.value))}
    />
  )
}

export default memo(NumberField)
