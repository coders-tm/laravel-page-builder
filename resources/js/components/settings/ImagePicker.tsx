import React, { useState } from "react"
import { Image, X } from "lucide-react"
import AssetModal from "@/components/assets/AssetModal"

/**
 * Image picker setting component.
 *
 * Used inside section/block settings to select an image
 * from the asset manager.
 *
 * @param {string} value - Current image URL
 * @param {Function} onChange - Called with new URL when asset is selected
 * @param {string} label - Optional label text
 * @param {string} info - Optional info text
 *
 * @example
 * <ImagePicker
 *   value={imageUrl}
 *   onChange={(url) => updateSetting("image", url)}
 * />
 */
export default function ImagePicker({ value, onChange, label, info }) {
  const [modalOpen, setModalOpen] = useState(false)

  const handleSelect = (asset) => {
    onChange(asset.url)
  }

  const handleRemove = (e) => {
    e.stopPropagation()
    onChange("")
  }

  return (
    <div className="mb-4">
      {label && (
        <label className="mb-1.5 block text-[11px] font-semibold tracking-wide text-gray-500">
          {label}
        </label>
      )}

      {value ? (
        /* ── Has image ── */
        <div className="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
          <img
            src={value}
            alt=""
            className="h-28 w-full object-cover"
            onError={(e) => {
              // e.target.style.display = "none";
            }}
          />
          <div className="absolute inset-0 flex items-center justify-center gap-2 bg-black/0 opacity-0 transition-colors group-hover:bg-black/30 group-hover:opacity-100">
            <button
              onClick={() => setModalOpen(true)}
              className="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50"
            >
              Change
            </button>
            <button
              onClick={handleRemove}
              className="rounded-md bg-white p-1.5 text-gray-500 shadow-sm transition-colors hover:bg-red-50 hover:text-red-500"
              title="Remove image"
            >
              <X className="h-3.5 w-3.5" />
            </button>
          </div>
        </div>
      ) : (
        /* ── No image ── */
        <button
          onClick={() => setModalOpen(true)}
          className="flex w-full cursor-pointer flex-col items-center justify-center gap-1.5 rounded-lg border-2 border-dashed border-gray-200 py-5 transition-colors hover:border-gray-300 hover:bg-gray-50/50"
        >
          <Image className="h-5 w-5 text-gray-400" />
          <span className="text-xs font-medium text-gray-500">Select image</span>
        </button>
      )}

      {info && <p className="mt-1 text-[10px] leading-relaxed text-gray-400">{info}</p>}

      <AssetModal isOpen={modalOpen} onClose={() => setModalOpen(false)} onSelect={handleSelect} />
    </div>
  )
}
