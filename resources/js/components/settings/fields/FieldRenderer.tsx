import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"
import { FieldRegistry, BaseFieldProps } from "@/core/registry/FieldRegistry"

export interface FieldRendererProps {
  setting: SettingSchema
  value: any
  onChange: (val: any) => void
}

function FieldRenderer({ setting, value, onChange }: FieldRendererProps) {
  const safeValue = value ?? setting.default ?? ""

  // Dynamically resolve component from registry
  let Component = FieldRegistry.get(setting.type)

  // Fallback if field type is not registered
  if (!Component) {
    console.warn(
      `FieldRenderer: No registered field for type "${setting.type}", falling back to text field.`,
    )
    Component = FieldRegistry.get("text")!
  }

  // Safety check in case the registry hasn't been initialized fully
  if (!Component) {
    return null
  }

  return <Component setting={setting} value={safeValue} onChange={onChange} />
}

export default memo(FieldRenderer)
