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
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"

interface SelectFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

function SelectField({ setting, value, onChange }: SelectFieldProps) {
  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger className="w-full">
        <SelectValue placeholder={setting.placeholder || "Select an option"} />
      </SelectTrigger>
      <SelectContent>
        {(setting.options || [])
          .filter((opt) => opt.value !== "")
          .map((opt) => (
            <SelectItem key={opt.value} value={opt.value}>
              {opt.label}
            </SelectItem>
          ))}
      </SelectContent>
    </Select>
  )
}

export default memo(SelectField)
