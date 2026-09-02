import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"

interface HeaderFieldProps {
  setting: SettingSchema
}

function HeaderField({ setting }: HeaderFieldProps) {
  return (
    <div className="mb-2 border-t border-gray-200 pt-3 pb-1 first:border-t-0 first:pt-0">
      <span className="text-[10px] font-bold tracking-widest text-gray-400 uppercase">
        {setting.content || setting.label}
      </span>
    </div>
  )
}

export default memo(HeaderField)
