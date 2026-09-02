import React, { memo } from "react"
import { SettingSchema } from "@/types/page-builder"
import { Input } from "@/components/ui/input"

interface TextFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

const inputCls =
  "flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"

function TextField({ setting, value, onChange }: TextFieldProps) {
  return (
    <Input
      type="text"
      value={value}
      placeholder={setting.placeholder || ""}
      onChange={(e) => onChange(e.target.value)}
    />
  )
}

export default memo(TextField)
export { inputCls }
