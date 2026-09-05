/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useEffect, useRef, useState } from "react"
import { Upload } from "lucide-react"
import Modal from "@/components/ui/Modal"
import Button from "@/components/ui/Button"
import AssetSearch from "@/components/assets/AssetSearch"
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
    loadingMore,
    uploading,
    search,
    total,
    hasMore,
    loadAssets,
    loadMoreAssets,
    uploadAsset,
    updateSearch,
  } = useAssets()

  const [selectedAsset, setSelectedAsset] = useState(null)
  const [isModalDragOver, setIsModalDragOver] = useState(false)
  const fileInputRef = useRef<HTMLInputElement>(null)
  const modalDragCounter = useRef(0)

  // Load assets when modal opens
  useEffect(() => {
    if (isOpen) {
      loadAssets({ page: 1, search: "" })
      setSelectedAsset(null)
      setIsModalDragOver(false)
      modalDragCounter.current = 0
    }
  }, [isOpen])

  // Support clipboard paste (Cmd+V / Ctrl+V)
  useEffect(() => {
    if (!isOpen) return

    const handlePaste = (e: ClipboardEvent) => {
      const items = e.clipboardData?.items
      if (!items) return

      for (let i = 0; i < items.length; i++) {
        const item = items[i]
        if (item.kind === "file" && item.type.startsWith("image/")) {
          const file = item.getAsFile()
          if (file) {
            handleUpload(file)
          }
        }
      }
    }

    window.addEventListener("paste", handlePaste)
    return () => {
      window.removeEventListener("paste", handlePaste)
    }
  }, [isOpen])

  const handleSelect = (asset) => {
    setSelectedAsset(asset)
    onSelect(asset)
    onClose()
  }

  const handleUpload = async (file) => {
    const asset = await uploadAsset(file)
    if (asset) {
      onSelect(asset)
      onClose()
    }
  }

  const handleUploadButtonClick = () => {
    fileInputRef.current?.click()
  }

  // Modal-wide Drag & Drop handlers
  const handleModalDragEnter = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    modalDragCounter.current += 1
    if (e.dataTransfer.types && Array.from(e.dataTransfer.types).includes("Files")) {
      setIsModalDragOver(true)
    }
  }

  const handleModalDragOver = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    e.dataTransfer.dropEffect = "copy"
  }

  const handleModalDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    modalDragCounter.current -= 1
    if (modalDragCounter.current <= 0) {
      modalDragCounter.current = 0
      setIsModalDragOver(false)
    }
  }

  const handleModalDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    modalDragCounter.current = 0
    setIsModalDragOver(false)

    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
      Array.from(e.dataTransfer.files).forEach((file) => handleUpload(file))
    }
  }

  const footer = (
    <div className="flex w-full items-center justify-between">
      <span className="text-xs text-gray-400">
        {total > 0
          ? `Showing ${assets.length} of ${total} asset${total !== 1 ? "s" : ""}`
          : "0 assets"}
      </span>
      <Button variant="secondary" size="sm" onClick={onClose}>
        Cancel
      </Button>
    </div>
  )

  return (
    <Modal isOpen={isOpen} onClose={onClose} title="Select asset" footer={footer}>
      <div
        onDragEnter={handleModalDragEnter}
        onDragOver={handleModalDragOver}
        onDragLeave={handleModalDragLeave}
        onDrop={handleModalDrop}
        className="relative max-h-[65vh] min-h-[300px] space-y-3 overflow-y-auto p-4"
      >
        {/* Modal Drag & Drop Overlay */}
        {isModalDragOver && (
          <div className="pointer-events-none absolute inset-0 z-50 flex flex-col items-center justify-center gap-3 rounded-lg bg-blue-600/90 p-6 text-center text-white backdrop-blur-xs transition-all">
            <div className="flex h-16 w-16 items-center justify-center rounded-full bg-white/20 ring-4 ring-white/30">
              <Upload className="h-8 w-8 animate-bounce text-white" />
            </div>
            <div>
              <p className="text-base font-semibold">Drop files anywhere to upload</p>
              <p className="mt-0.5 text-xs text-blue-100">
                Supports image files (PNG, JPG, GIF, WebP, SVG)
              </p>
            </div>
          </div>
        )}

        {/* Search + Upload button */}
        <div className="flex items-center gap-2">
          <div className="flex-1">
            <AssetSearch value={search} onChange={updateSearch} />
          </div>
          <Button
            size="sm"
            variant="secondary"
            onClick={handleUploadButtonClick}
            disabled={uploading}
          >
            <Upload className="h-3.5 w-3.5" />
            Upload
          </Button>
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            multiple
            className="hidden"
            onChange={(e) => {
              if (e.target.files) {
                Array.from(e.target.files).forEach((file) => handleUpload(file))
              }
              e.target.value = ""
            }}
          />
        </div>

        {/* Asset grid with infinite scroll */}
        <AssetGrid
          assets={assets}
          selectedId={selectedAsset?.id}
          onSelect={handleSelect}
          loading={loading}
          uploading={uploading}
          onUpload={handleUpload}
          hasMore={hasMore}
          loadingMore={loadingMore}
          onLoadMore={loadMoreAssets}
        />
      </div>
    </Modal>
  )
}
