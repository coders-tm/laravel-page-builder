import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"

interface RadioFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

function RadioField({ setting, value, onChange }: RadioFieldProps) {
  return (
    <div className="flex flex-col gap-1.5">
      {(setting.options || []).map((opt) => (
        <label key={opt.value} className="flex cursor-pointer items-center gap-2">
          <input
            type="radio"
            name={`radio-${setting.id}`}
            className="h-3.5 w-3.5 cursor-pointer accent-blue-500"
            checked={value === opt.value}
            onChange={() => onChange(opt.value)}
          />
          <span className="text-xs text-gray-700">{opt.label}</span>
        </label>
      ))}
    </div>
  )
}

export default memo(RadioField)
