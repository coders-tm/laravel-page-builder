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
import IconPickerField from "./IconPickerField"
import MD_ICONS from "./icon-data/md-icons"

/**
 * MD font variant options for the `md_font` sub-option on the setting schema.
 * When not specified the picker defaults to "all variants" mode showing all 5.
 * Specify a variant to restrict the picker to only that group.
 */
type MdFontVariant =
  | "material-icons"
  | "material-icons-outlined"
  | "material-icons-round"
  | "material-icons-sharp"
  | "material-icons-two-tone"

interface MaterialIconFieldProps {
  setting: SettingSchema & { md_font?: MdFontVariant }
  value: string
  onChange: (val: string) => void
}

/**
 * MaterialIconField
 *
 * Setting type: `icon_md`
 *
 * Stores the Material Design icon ligature name (e.g. "home").
 * Requires the Material Icons font to be loaded in the page/theme, e.g.:
 *   <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
 *
 * You can control the MD variant via the optional `md_font` key on the setting:
 *   `md_font: "material-icons-outlined"` → renders with the outlined variant
 *
 * Schema example:
 * ```php
 * [
 *   'id'      => 'icon',
 *   'type'    => 'icon_md',
 *   'label'   => 'Icon',
 *   'default' => 'star',
 *   'md_font' => 'material-icons-outlined',   // optional
 * ]
 * ```
 *
 * Blade usage:
 * ```blade
 * <span class="material-icons">{{ $section->settings->icon }}</span>
 * ```
 */
function MaterialIconField({ setting, value, onChange }: MaterialIconFieldProps) {
  const mdFontClass: MdFontVariant = setting.md_font || "material-icons"

  // If a specific variant is pinned via `md_font`, filter icons to that group only.
  // Otherwise, show all groups so users can browse all 5 variants.
  const icons = setting.md_font ? MD_ICONS.filter((ic) => ic.group === mdFontClass) : MD_ICONS

  return (
    <IconPickerField
      value={value}
      onChange={onChange}
      icons={icons}
      variant="md"
      mdFontClass={mdFontClass}
      placeholder={setting.placeholder || "Select a Material icon…"}
    />
  )
}

export default memo(MaterialIconField)
