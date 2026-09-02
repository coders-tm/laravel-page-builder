import { useState, useCallback, useRef, useEffect } from "react"
import { useEditorInstance } from "@/core/editorContext"

/**
 * Custom hook for managing assets.
 *
 * Provides a complete API for listing, searching,
 * and uploading assets with pagination.
 *
 * Internally uses AssetService from the Editor instance,
 * so UI components never touch the provider or API directly.
 *
 * @example
 * const { assets, loading, loadAssets, uploadAsset } = useAssets();
 */
export function useAssets() {
  const editor = useEditorInstance()
  const assetService = editor.assets

  const [assets, setAssets] = useState([])
  const [loading, setLoading] = useState(false)
  const [uploading, setUploading] = useState(false)
  const [search, setSearch] = useState("")
  const [page, setPage] = useState(1)
  const [perPage, setPerPage] = useState(20)
  const [total, setTotal] = useState(0)

  const searchTimerRef = useRef(null)

  /**
   * Load assets from the service.
   */
  const loadAssets = useCallback(
    async (params: { page?: number; search?: string } = {}) => {
      setLoading(true)
      try {
        const result = await assetService.list({
          page: params.page ?? page,
          search: params.search ?? search,
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
    [assetService, page, search],
  )

  /**
   * Upload a file.
   * Inserts the new asset at the start of the grid on success.
   */
  const uploadAsset = useCallback(
    async (file) => {
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
    (newPage) => {
      setPage(newPage)
      loadAssets({ page: newPage, search })
    },
    [loadAssets, search],
  )

  /**
   * Update search term with debounce.
   */
  const updateSearch = useCallback(
    (query) => {
      setSearch(query)
      clearTimeout(searchTimerRef.current)
      searchTimerRef.current = setTimeout(() => {
        setPage(1)
        loadAssets({ page: 1, search: query })
      }, 300)
    },
    [loadAssets],
  )

  // Cleanup debounce timer
  useEffect(() => {
    return () => clearTimeout(searchTimerRef.current)
  }, [])

  return {
    assets,
    loading,
    uploading,
    search,
    page,
    perPage,
    total,
    loadAssets,
    uploadAsset,
    selectPage,
    updateSearch,
  }
}
