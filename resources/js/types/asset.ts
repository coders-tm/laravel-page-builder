/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
export interface Asset {
  id: string
  name: string
  url: string
  thumbnail: string
  size: number
  type: string
}

/**
 * Pagination metadata.
 */
export interface Pagination {
  page: number
  per_page: number
  total: number
}

/**
 * Paginated asset list response.
 */
export interface AssetList {
  data: Asset[]
  pagination: Pagination
}

/**
 * Asset provider interface.
 *
 * All providers (Laravel, S3, Cloudinary, Unsplash, etc.)
 * must implement this interface.
 */
export interface AssetProvider {
  list(params: { page?: number; search?: string }): Promise<AssetList>
  upload(file: File): Promise<Asset>
}
