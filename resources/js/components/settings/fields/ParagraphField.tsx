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
