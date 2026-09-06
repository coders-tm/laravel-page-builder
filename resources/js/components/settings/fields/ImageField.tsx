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
import ImagePicker from "@/components/settings/ImagePicker"

interface ImageFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
}

function ImageField({ setting, value, onChange }: ImageFieldProps) {
  return (
    <div data-setting-id={setting.id}>
      <ImagePicker value={value} onChange={onChange} label={setting.label} info={setting.info} />
    </div>
  )
}

export default memo(ImageField)
