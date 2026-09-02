import React from "react"
import { SettingSchema } from "@/types/page-builder"

export interface FieldWrapperProps {
  setting: SettingSchema
  children: React.ReactNode
  /** Omit top-level label (used by Checkbox, Range, Header, Paragraph) */
  noLabel?: boolean
  /** Extra class on the outer div */
  className?: string
}

export function FieldWrapper({
  setting,
  children,
  noLabel = false,
  className = "mb-4",
}: FieldWrapperProps) {
  return (
    <div className={className} data-setting-id={setting.id}>
      {!noLabel && setting.label && (
        <label className="mb-1.5 block text-[11px] font-semibold tracking-wide text-gray-500">
          {setting.label}
        </label>
      )}
      {children}
      {setting.info && (
        <p className="mt-1 text-[10px] leading-relaxed text-gray-400">{setting.info}</p>
      )}
    </div>
  )
}

export default FieldWrapper
