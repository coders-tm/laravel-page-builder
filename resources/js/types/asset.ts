/**
 * Asset object returned by the API.
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
