/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useRef, useState } from "react"
import { Upload, Loader2 } from "lucide-react"
import { cn } from "@/lib/utils"

interface UploadZoneProps {
  onUpload: (file: File) => void
  uploading?: boolean
  variant?: "full" | "card"
  className?: string
}

/**
 * Upload zone with drag-and-drop and click-to-upload.
 * Supports full container or card variant inside asset grids.
 */
export default function UploadZone({
  onUpload,
  uploading = false,
  variant = "full",
  className,
}: UploadZoneProps) {
  const inputRef = useRef<HTMLInputElement>(null)
  const dragCounter = useRef(0)
  const [dragOver, setDragOver] = useState(false)

  const handleFiles = (files: FileList | null) => {
    if (!files || files.length === 0) return
    Array.from(files).forEach((file) => onUpload(file))
  }

  const handleDragEnter = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    dragCounter.current += 1
    if (e.dataTransfer.types && Array.from(e.dataTransfer.types).includes("Files")) {
      setDragOver(true)
    }
  }

  const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    e.dataTransfer.dropEffect = "copy"
  }

  const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    dragCounter.current -= 1
    if (dragCounter.current <= 0) {
      dragCounter.current = 0
      setDragOver(false)
    }
  }

  const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
    e.preventDefault()
    e.stopPropagation()
    dragCounter.current = 0
    setDragOver(false)
    handleFiles(e.dataTransfer.files)
  }

  if (variant === "card") {
    return (
      <div
        onDragEnter={handleDragEnter}
        onDragOver={handleDragOver}
        onDragLeave={handleDragLeave}
        onDrop={handleDrop}
        onClick={() => inputRef.current?.click()}
        className={cn(
          "group relative flex aspect-square cursor-pointer flex-col items-center justify-center overflow-hidden rounded-lg border-2 border-dashed p-2 text-center transition-all",
          dragOver
            ? "border-blue-500 bg-blue-50 ring-2 ring-blue-200"
            : "border-gray-300 bg-gray-50/50 hover:border-blue-500 hover:bg-blue-50/50",
          uploading && "pointer-events-none opacity-60",
          className,
        )}
      >
        {uploading ? (
          <>
            <Loader2 className="h-5 w-5 animate-spin text-blue-500" />
            <span className="mt-1 text-[10px] font-medium text-gray-500">Uploading…</span>
          </>
        ) : (
          <>
            <Upload
              className={cn(
                "h-5 w-5 transition-colors",
                dragOver ? "text-blue-500" : "text-gray-400 group-hover:text-blue-500",
              )}
            />
            <span
              className={cn(
                "mt-1 text-[11px] font-medium text-gray-600 transition-colors",
                dragOver ? "font-semibold text-blue-600" : "group-hover:text-blue-600",
              )}
            >
              Upload
            </span>
          </>
        )}
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

  return (
    <div
      onDragEnter={handleDragEnter}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
      onClick={() => inputRef.current?.click()}
      className={cn(
        "group my-2 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-8 text-center transition-all",
        dragOver
          ? "border-blue-500 bg-blue-50 ring-4 ring-blue-100"
          : "border-gray-300 bg-gray-50/50 hover:border-blue-400 hover:bg-blue-50/30",
        uploading && "pointer-events-none opacity-60",
        className,
      )}
    >
      {uploading ? (
        <>
          <Loader2 className="h-8 w-8 animate-spin text-blue-500" />
          <span className="text-xs font-medium text-gray-600">Uploading assets…</span>
        </>
      ) : (
        <>
          <div
            className={cn(
              "flex h-12 w-12 items-center justify-center rounded-full transition-all",
              dragOver
                ? "scale-110 bg-blue-500 text-white"
                : "bg-blue-50 text-blue-500 group-hover:scale-110",
            )}
          >
            <Upload className="h-6 w-6" />
          </div>
          <div className="space-y-0.5">
            <p className="text-sm font-medium text-gray-700 transition-colors group-hover:text-blue-600">
              {dragOver ? "Drop files now to upload" : "Drop files here or click to upload"}
            </p>
            <p className="text-xs text-gray-400">Supports images (PNG, JPG, GIF, WebP, SVG)</p>
          </div>
        </>
      )}
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
