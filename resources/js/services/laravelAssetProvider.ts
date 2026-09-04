/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import config from "@/config"
import type { AssetProvider, AssetList, Asset } from "@/types/asset"

/**
 * Get the CSRF token from the meta tag.
 */
function getCsrfToken(): string {
  const meta = document.querySelector('meta[name="csrf-token"]')
  return meta ? meta.getAttribute("content") || "" : ""
}

/**
 * Laravel asset provider.
 *
 * Communicates with the Laravel backend to manage assets
 * stored in `storage/app/public/pagebuilder`.
 *
 * Note: config.baseUrl is read lazily (inside each method) to avoid
 * a circular-dependency initialisation error — config.tsx imports this
 * file, so any top-level access to `config` here would run before the
 * config object is fully constructed.
 */
const laravelAssetProvider: AssetProvider = {
  async list(params: { page?: number; search?: string } = {}): Promise<AssetList> {
    const query = new URLSearchParams()
    if (params.page) query.set("page", String(params.page))
    if (params.search) query.set("q", params.search)

    const res = await fetch(`${config.baseUrl}/assets?${query.toString()}`)
    if (!res.ok) throw new Error("Failed to fetch assets")
    return res.json()
  },

  async upload(file: File): Promise<Asset> {
    const formData = new FormData()
    formData.append("file", file)

    const res = await fetch(`${config.baseUrl}/assets/upload`, {
      method: "POST",
      headers: {
        "X-CSRF-TOKEN": getCsrfToken(),
      },
      body: formData,
    })

    if (!res.ok) throw new Error("Failed to upload asset")
    return res.json()
  },
}

export default laravelAssetProvider
