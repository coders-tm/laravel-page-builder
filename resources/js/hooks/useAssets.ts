/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { useState, useCallback, useRef, useEffect } from "react"
import { useEditorInstance } from "@/core/editorContext"
import type { Asset } from "@/types/asset"

/**
 * Custom hook for managing assets.
 *
 * Provides a complete API for listing, searching,
 * and uploading assets with infinite scroll pagination.
 *
 * Internally uses AssetService from the Editor instance,
 * so UI components never touch the provider or API directly.
 *
 * @example
 * const { assets, loading, loadAssets, loadMoreAssets, uploadAsset } = useAssets();
 */
export function useAssets() {
  const editor = useEditorInstance()
  const assetService = editor.assets

  const [assets, setAssets] = useState<Asset[]>([])
  const [loading, setLoading] = useState(false)
  const [loadingMore, setLoadingMore] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [search, setSearch] = useState("")
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(20)
  const [total, setTotal] = useState(0)

  const searchTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)

  /**
   * Initial load or search query load (replaces assets list).
   */
  const loadAssets = useCallback(
    async (params: { page?: number; search?: string } = {}) => {
      setLoading(true)
      const targetPage = params.page ?? 1
      const targetSearch = params.search ?? search
      try {
        const result = await assetService.list({
          page: targetPage,
          search: targetSearch,
        })
        setAssets(result.data)
        setPage(result.pagination.page)
        setPerPage(result.pagination.per_page)
        setTotal(result.pagination.total)
      } catch (err) {
        console.error("Failed to load assets:", err)
      } finally {
        setLoading(false)
      }
    },
    [assetService, search],
  )

  /**
   * Load next page for infinite scrolling (appends newly loaded assets).
   */
  const loadMoreAssets = useCallback(async () => {
    const totalPages = Math.ceil(total / perPage)
    if (loading || loadingMore || page >= totalPages) return

    setLoadingMore(true)
    const nextPage = page + 1
    try {
      const result = await assetService.list({
        page: nextPage,
        search,
      })
      setAssets((prev) => [...prev, ...result.data])
      setPage(result.pagination.page)
      setPerPage(result.pagination.per_page)
      setTotal(result.pagination.total)
    } catch (err) {
      console.error("Failed to load more assets:", err)
    } finally {
      setLoadingMore(false)
    }
  }, [assetService, loading, loadingMore, page, perPage, total, search])

  /**
   * Upload a file.
   * Inserts the new asset at the start of the grid on success.
   */
  const uploadAsset = useCallback(
    async (file: File) => {
      setUploading(true)
      try {
        const asset = await assetService.upload(file)
        setAssets((prev) => [asset, ...prev])
        setTotal((prev) => prev + 1)
        return asset
      } catch (err) {
        console.error("Failed to upload asset:", err)
        return null
      } finally {
        setUploading(false)
      }
    },
    [assetService],
  )

  /**
   * Navigate to a specific page.
   */
  const selectPage = useCallback(
    (newPage: number) => {
      setPage(newPage)
      loadAssets({ page: newPage, search })
    },
    [loadAssets, search],
  )

  /**
   * Update search term with debounce.
   */
  const updateSearch = useCallback(
    (query: string) => {
      setSearch(query)
      if (searchTimerRef.current) {
        clearTimeout(searchTimerRef.current)
      }
      searchTimerRef.current = setTimeout(() => {
        setPage(1)
        loadAssets({ page: 1, search: query })
      }, 300)
    },
    [loadAssets],
  )

  // Cleanup debounce timer
  useEffect(() => {
    return () => {
      if (searchTimerRef.current) {
        clearTimeout(searchTimerRef.current)
      }
    }
  }, [])

  const hasMore = page < Math.ceil(total / perPage)

  return {
    assets,
    loading,
    loadingMore,
    uploading,
    search,
    page,
    perPage,
    total,
    hasMore,
    loadAssets,
    loadMoreAssets,
    uploadAsset,
    selectPage,
    updateSearch,
  }
}
