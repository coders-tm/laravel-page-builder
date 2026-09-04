/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useState } from "react"
import { Search } from "lucide-react"
import { Input } from "@/components/ui/input"

/**
 * Asset search input.
 *
 * @param {string} value - Current search query
 * @param {Function} onChange - Called with new query string
 */
export default function AssetSearch({ value, onChange }) {
  return (
    <Input
      type="text"
      icon={<Search className="h-3.5 w-3.5" />}
      value={value}
      placeholder="Search assets…"
      onChange={(e) => onChange(e.target.value)}
    />
  )
}
