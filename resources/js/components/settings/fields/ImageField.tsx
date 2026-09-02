import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"
import ImagePicker from "@/components/settings/ImagePicker"

interface ImageFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

function ImageField({ setting, value, onChange }: ImageFieldProps) {
  return <ImagePicker value={value} onChange={onChange} label={setting.label} info={setting.info} />
}

export default memo(ImageField)
