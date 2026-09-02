import React, { useRef, useState } from "react"
import { Upload } from "lucide-react"
import { cn } from "@/lib/utils"

/**
 * Upload zone with drag-and-drop and click-to-upload.
 *
 * @param {Function} onUpload - Called with File when a file is selected
 * @param {boolean} uploading - Whether an upload is in progress
 */
export default function UploadZone({ onUpload, uploading = false }) {
  const inputRef = useRef(null)
  const [dragOver, setDragOver] = useState(false)

  const handleFiles = (files) => {
    if (!files || files.length === 0) return
    Array.from(files).forEach((file) => onUpload(file))
  }

  const handleDrop = (e) => {
    e.preventDefault()
    setDragOver(false)
    handleFiles(e.dataTransfer.files)
  }

  const handleDragOver = (e) => {
    e.preventDefault()
    setDragOver(true)
  }

  const handleDragLeave = () => {
    setDragOver(false)
  }

  return (
    <div
      onDrop={handleDrop}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onClick={() => inputRef.current?.click()}
      className={cn(
        "flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-4 transition-colors",
        dragOver
          ? "border-blue-400 bg-blue-50"
          : "border-gray-200 bg-gray-50/50 hover:border-gray-300",
        uploading && "pointer-events-none opacity-50",
      )}
    >
      <Upload className={cn("h-5 w-5", dragOver ? "text-blue-500" : "text-gray-400")} />
      <span className="text-center text-xs text-gray-500">
        {uploading ? "Uploading…" : "Drop files here or click to upload"}
      </span>
      <input
        ref={inputRef}
        type="file"
        accept="image/*"
        multiple
        className="hidden"
        onChange={(e) => {
          handleFiles(e.target.files)
          e.target.value = ""
        }}
      />
    </div>
  )
}
