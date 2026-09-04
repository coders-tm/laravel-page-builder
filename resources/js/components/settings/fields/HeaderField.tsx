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
