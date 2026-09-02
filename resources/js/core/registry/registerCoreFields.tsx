import React from "react"
import { FieldRegistry, BaseFieldProps } from "@/core/registry/FieldRegistry"
import FieldWrapper from "@/components/settings/fields/FieldWrapper"
import CustomFieldWrapper from "@/components/settings/fields/CustomFieldWrapper"
import config from "@/config"

// Import all core field components
import TextField from "@/components/settings/fields/TextField"
import NumberField from "@/components/settings/fields/NumberField"
import TextareaField from "@/components/settings/fields/TextareaField"
import SelectField from "@/components/settings/fields/SelectField"
import RadioField from "@/components/settings/fields/RadioField"
import CheckboxField from "@/components/settings/fields/CheckboxField"
import RangeField from "@/components/settings/fields/RangeField"
import ColorField from "@/components/settings/fields/ColorField"
import AlignmentField from "@/components/settings/fields/AlignmentField"
import ImageField from "@/components/settings/fields/ImageField"
import HeaderField from "@/components/settings/fields/HeaderField"
import ParagraphField from "@/components/settings/fields/ParagraphField"
import RichTextField from "@/components/settings/fields/RichTextField"
import ExternalField from "@/components/settings/fields/ExternalField"
import FontAwesomeField from "@/components/settings/fields/FontAwesomeField"
import MaterialIconField from "@/components/settings/fields/MaterialIconField"
import GoogleFontField from "@/components/settings/fields/GoogleFontField"

/**
 * Register a field type that renders a component inside a FieldWrapper.
 * Eliminates the repetitive `FieldRegistry.register(type, ({setting,value,onChange}) => <FieldWrapper><Component … /></FieldWrapper>)` pattern.
 */
function registerWrapped(
  type: string,
  Component: React.ComponentType<BaseFieldProps & Record<string, any>>,
  wrapperProps?: Record<string, any>,
  extraProps?: Record<string, any>,
): void {
  FieldRegistry.register(type, ({ setting, value, onChange }: BaseFieldProps) => (
    <FieldWrapper setting={setting} {...(wrapperProps || {})}>
      <Component setting={setting} value={value} onChange={onChange} {...(extraProps || {})} />
    </FieldWrapper>
  ))
}

export function registerCoreFields() {
  // 1. Standard wrapped fields
  registerWrapped("text", TextField)
  registerWrapped("inline_richtext", TextField)
  registerWrapped("number", NumberField)
  registerWrapped("richtext", RichTextField)
  registerWrapped("textarea", TextareaField)
  registerWrapped("select", SelectField)
  registerWrapped("radio", RadioField)
  registerWrapped("color", ColorField)
  registerWrapped("text_alignment", AlignmentField)

  // 2. Wrapped fields with extra component props
  registerWrapped("color_background", ColorField, undefined, {
    isBackground: true,
  })
  registerWrapped("checkbox", CheckboxField, {
    noLabel: true,
    className: "mb-3",
  })

  // 3. Fields with custom wrapper props or settings overrides
  FieldRegistry.register("url", ({ setting, value, onChange }: BaseFieldProps) => (
    <FieldWrapper setting={setting}>
      <TextField
        setting={{
          ...setting,
          placeholder: setting.placeholder || "https://…",
        }}
        value={value}
        onChange={onChange}
      />
    </FieldWrapper>
  ))

  FieldRegistry.register("html", ({ setting, value, onChange }: BaseFieldProps) => (
    <FieldWrapper setting={setting}>
      <TextareaField
        setting={{
          ...setting,
          placeholder: setting.placeholder || "<div>…</div>",
        }}
        value={value}
        onChange={onChange}
        mono
      />
    </FieldWrapper>
  ))

  FieldRegistry.register("range", ({ setting, value, onChange }: BaseFieldProps) => (
    <FieldWrapper setting={setting} noLabel>
      <RangeField setting={setting} value={value} onChange={onChange} />
      {setting.info && (
        <p className="mt-1 text-[10px] leading-relaxed text-gray-400">{setting.info}</p>
      )}
    </FieldWrapper>
  ))

  FieldRegistry.register("video_url", ({ setting, value, onChange }: BaseFieldProps) => (
    <FieldWrapper setting={setting}>
      <TextField
        setting={{
          ...setting,
          placeholder:
            setting.placeholder || "https://www.youtube.com/watch?v=… or https://vimeo.com/…",
        }}
        value={value}
        onChange={onChange}
      />
      {value && <p className="mt-1 text-[10px] text-gray-400">Supports YouTube and Vimeo URLs</p>}
    </FieldWrapper>
  ))

  // 4. Self-wrapping fields (no FieldWrapper needed)
  FieldRegistry.register("image_picker", ({ setting, value, onChange }: BaseFieldProps) => (
    <ImageField setting={setting} value={value} onChange={onChange} />
  ))

  FieldRegistry.register("header", ({ setting, value, onChange }: BaseFieldProps) => (
    <HeaderField setting={setting} value={value} onChange={onChange} />
  ))

  FieldRegistry.register("paragraph", ({ setting, value, onChange }: BaseFieldProps) => (
    <ParagraphField setting={setting} value={value} onChange={onChange} />
  ))

  // 4b. Icon picker fields
  registerWrapped("icon_fa", FontAwesomeField)
  registerWrapped("icon_md", MaterialIconField)

  // 4c. Google Font picker
  registerWrapped("google_font", GoogleFontField)

  // 5. Register legacy or external dynamic config fields
  if (config.fields) {
    Object.entries(config.fields).forEach(([type, fieldDef]) => {
      if (typeof fieldDef === "object" && fieldDef.type === "external") {
        FieldRegistry.register(type, ({ setting, value, onChange }: BaseFieldProps) => (
          <FieldWrapper setting={setting}>
            <ExternalField
              setting={setting}
              value={value}
              onChange={onChange}
              fetchList={fieldDef.fetchList}
            />
          </FieldWrapper>
        ))
      } else if (typeof fieldDef === "function") {
        FieldRegistry.register(type, ({ setting, value, onChange }: BaseFieldProps) => (
          <FieldWrapper setting={setting}>
            <CustomFieldWrapper
              renderer={fieldDef as any}
              setting={setting}
              value={value}
              onChange={onChange}
            />
          </FieldWrapper>
        ))
      }
    })
  }
}
