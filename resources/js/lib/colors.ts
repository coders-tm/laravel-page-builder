/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { colord } from "colord"

/**
 * Convert a hex color to wrapped HSL values.
 * e.g., #10b981 -> hsl(161 84% 39%)
 */
export function hexToHsl(hex: string): string {
  return colord(hex).toHslString().replace(/,/g, "")
}

/**
 * Convert HSL or Hex string to a hex color.
 */
export function hslToHex(hsl: string): string {
  return colord(hsl).toHex()
}
