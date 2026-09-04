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
import FA_ICONS from "./icon-data/fa-icons"

interface FontAwesomeFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

/**
 * FontAwesomeField
 *
 * Setting type: `icon_fa`
 *
 * Stores the full FA class string (e.g. "fas fa-home").
 * Requires FontAwesome Pro (or Free) CSS to be loaded in the page/theme.
 *
 * Schema example:
 * ```php
 * ['id' => 'icon', 'type' => 'icon_fa', 'label' => 'Icon', 'default' => 'fas fa-star']
 * ```
 *
 * Blade usage:
 * ```blade
 * <i class="{{ $section->settings->icon }}"></i>
 * ```
 */
function FontAwesomeField({ setting, value, onChange }: FontAwesomeFieldProps) {
  return (
    <IconPickerField
      value={value}
      onChange={onChange}
      icons={FA_ICONS}
      variant="fa"
      placeholder={setting.placeholder || "Select a FontAwesome icon…"}
    />
  )
}

export default memo(FontAwesomeField)
