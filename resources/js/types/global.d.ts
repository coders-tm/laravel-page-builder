/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { Page, SectionSchema, SectionData } from "./page-builder"

declare global {
  interface Window {
    PageBuilder?: {
      pages?: Page[]
      sections?: Record<string, SectionData>
      config?: {
        baseUrl: string
        csrfToken: string
      }
      baseUrl?: string
      fieldTypes?: Record<
        string,
        (args: {
          setting: any
          value: any
          onChange: (val: any) => void
          container: HTMLElement
        }) => void | string | HTMLElement
      >
    }
  }
}

export {}
