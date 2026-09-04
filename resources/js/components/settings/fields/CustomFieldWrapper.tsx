/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { memo, useRef, useEffect } from "react"
import { SettingSchema } from "@/types/page-builder"

export interface CustomFieldWrapperProps {
  renderer: (args: {
    setting: SettingSchema
    value: any
    onChange: (val: any) => void
    container: HTMLElement
  }) => void | string | HTMLElement
  setting: SettingSchema
  value: any
  onChange: (val: any) => void
}

export const CustomFieldWrapper = memo(function CustomFieldWrapper({
  renderer,
  setting,
  value,
  onChange,
}: CustomFieldWrapperProps) {
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    if (!containerRef.current || !renderer) return
    containerRef.current.innerHTML = ""
    try {
      const result = renderer({
        setting,
        value,
        onChange,
        container: containerRef.current,
      })
      if (result instanceof HTMLElement) {
        containerRef.current.appendChild(result)
      } else if (typeof result === "string") {
        containerRef.current.innerHTML = result
      }
    } catch (e: any) {
      containerRef.current.innerHTML = `<span class="text-xs text-red-500">Error rendering custom field: ${e.message}</span>`
    }
  }, [renderer, setting, value, onChange])

  return <div ref={containerRef} />
})

export default CustomFieldWrapper
