import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"

interface ParagraphFieldProps {
  setting: SettingSchema
}

function ParagraphField({ setting }: ParagraphFieldProps) {
  return (
    <div className="mb-3 text-xs leading-relaxed text-gray-500">
      {setting.content || setting.label}
    </div>
  )
}

export default memo(ParagraphField)
