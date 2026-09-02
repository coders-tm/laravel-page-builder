import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"

interface CheckboxFieldProps {
  setting: SettingSchema
  value: boolean
  onChange: (val: boolean) => void
}

function CheckboxField({ setting, value, onChange }: CheckboxFieldProps) {
  return (
    <label className="flex cursor-pointer items-center gap-2">
      <input
        type="checkbox"
        className="h-3.5 w-3.5 cursor-pointer rounded accent-blue-500"
        checked={!!value}
        onChange={(e) => onChange(e.target.checked)}
      />
      <span className="text-xs font-medium text-gray-700">{setting.label}</span>
    </label>
  )
}

export default memo(CheckboxField)
