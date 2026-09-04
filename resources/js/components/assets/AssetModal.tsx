/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useEffect, useState } from "react"
import { Upload } from "lucide-react"
import Modal from "@/components/ui/Modal"
import Button from "@/components/ui/Button"
import AssetSearch from "@/components/assets/AssetSearch"
import UploadZone from "@/components/assets/UploadZone"
import AssetGrid from "@/components/assets/AssetGrid"
import { useAssets } from "@/hooks/useAssets"

/**
 * Asset manager modal.
 *
 * Composes search, upload, and grid into a cohesive
 * asset browsing experience with pagination.
 *
 * @param {boolean} isOpen - Whether the modal is visible
 * @param {Function} onClose - Close callback
 * @param {Function} onSelect - Called with the selected Asset object
 */
export default function AssetModal({ isOpen, onClose, onSelect }) {
  const {
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
  } = useAssets()

  const [selectedAsset, setSelectedAsset] = useState(null)
  const [showUpload, setShowUpload] = useState(false)

  // Load assets when modal opens
  useEffect(() => {
    if (isOpen) {
      loadAssets({ page: 1, search: "" })
      setSelectedAsset(null)
      setShowUpload(false)
    }
  }, [isOpen])

  const handleSelect = (asset) => {
    setSelectedAsset(asset)
  }

  const handleConfirm = () => {
    if (selectedAsset) {
      onSelect(selectedAsset)
      onClose()
    }
  }

  const handleUpload = async (file) => {
    const asset = await uploadAsset(file)
    if (asset) {
      setSelectedAsset(asset)
      setShowUpload(false)
    }
  }

  // Pagination
  const totalPages = Math.ceil(total / perPage)
  const hasPrev = page > 1
  const hasNext = page < totalPages

  const footer = (
    <div className="flex items-center justify-between">
      <div className="flex items-center gap-2">
        {totalPages > 1 && (
          <>
            <Button
              size="sm"
              variant="secondary"
              disabled={!hasPrev}
              onClick={() => selectPage(page - 1)}
            >
              ‹ Prev
            </Button>
            <span className="text-xs text-gray-500">
              {page} / {totalPages}
            </span>
            <Button
              size="sm"
              variant="secondary"
              disabled={!hasNext}
              onClick={() => selectPage(page + 1)}
            >
              Next ›
            </Button>
          </>
        )}
        {total > 0 && (
          <span className="ml-1 text-[10px] text-gray-400">
            {total} asset{total !== 1 ? "s" : ""}
          </span>
        )}
      </div>
      <Button variant="primary" size="sm" disabled={!selectedAsset} onClick={handleConfirm}>
        Select
      </Button>
    </div>
  )

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Select asset" footer={footer}>
      <div className="space-y-3 p-4">
        {/* Search + Upload toggle */}
        <div className="flex items-center gap-2">
          <div className="flex-1">
            <AssetSearch value={search} onChange={updateSearch} />
          </div>
          <Button
            size="sm"
            variant={showUpload ? "primary" : "secondary"}
            onClick={() => setShowUpload((v) => !v)}
          >
            <Upload className="h-3.5 w-3.5" />
            Upload
          </Button>
        </div>

        {/* Upload zone (collapsible) */}
        {showUpload && <UploadZone onUpload={handleUpload} uploading={uploading} />}

        {/* Asset grid */}
        <AssetGrid
          assets={assets}
          selectedId={selectedAsset?.id}
          onSelect={handleSelect}
          loading={loading}
        />
      </div>
    </Modal>
  )
}
