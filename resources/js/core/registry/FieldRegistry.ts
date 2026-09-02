import React from "react"
import { SettingSchema } from "@/types/page-builder"

/**
 * Props passed to every registered field component.
 */
export interface BaseFieldProps<TValue = any> {
  setting: SettingSchema
  value: TValue
  onChange: (value: TValue) => void
}

class Registry {
  private fields = new Map<string, React.FC<BaseFieldProps>>()

  register(type: string, component: React.FC<BaseFieldProps>) {
    this.fields.set(type, component)
  }

  get(type: string): React.FC<BaseFieldProps> | undefined {
    return this.fields.get(type)
  }

  has(type: string): boolean {
    return this.fields.has(type)
  }
}

export const FieldRegistry = new Registry()
