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

interface RangeFieldProps {
  setting: SettingSchema
  value: number
  onChange: (val: number) => void
}

function RangeField({ setting, value, onChange }: RangeFieldProps) {
  return (
    <>
      <div className="mb-1 flex justify-between">
        <label className="text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
          {setting.label}
        </label>
        <span className="text-[11px] font-bold text-blue-600">
          {value}
          {setting.unit || ""}
        </span>
      </div>
      <input
        type="range"
        className="w-full accent-blue-500"
        min={setting.min ?? 0}
        max={setting.max ?? 100}
        step={setting.step ?? 1}
        value={value}
        onChange={(e) => onChange(Number(e.target.value))}
      />
      <div className="mt-0.5 flex justify-between text-[10px] text-gray-400">
        <span>{setting.min ?? 0}</span>
        <span>{setting.max ?? 100}</span>
      </div>
    </>
  )
}

export default memo(RangeField)
