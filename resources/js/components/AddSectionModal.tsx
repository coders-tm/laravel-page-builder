/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useCallback, useEffect, useMemo, useRef, useState } from "react"
import { Layout, Search, ChevronLeft, ArrowRight } from "lucide-react"
import api from "@/services/api"
import { injectEditorScript } from "@/core/messaging/EditorScriptInjector"
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { Drawer, DrawerContent, DrawerHeader, DrawerTitle } from "@/components/ui/drawer"
import { useBreakpoint } from "@/hooks/useBreakpoint"
import { cn } from "@/lib/utils"
import { useEditorInstance } from "@/core/editorContext"
import { useEditorLayout } from "@/hooks/useEditorLayout"
import { useEditorNavigation } from "@/hooks/useEditorNavigation"
import { parsePresetBlocks } from "@/core/utils/blocks"
import { useStore } from "@/core/store/useStore"

interface SectionEntry {
  type: string
  meta: Record<string, any>
  label: string
}

const PREVIEW_IFRAME_WIDTH = 1400

function buildSectionPreviewPayload(
  type: string,
  meta: Record<string, any>,
  presetIndex: number = 0,
  themeBlocks: Record<string, any> = {},
) {
  const schema = (meta?.schema || meta || {}) as Record<string, any>

  const settings: Record<string, any> = {}
  ;(schema.settings || []).forEach((s: any) => {
    if (s?.default !== undefined) settings[s.id] = s.default
  })

  const blocks: Record<string, any> = {}
  const order: string[] = []

  const preset = Array.isArray(schema.presets)
    ? schema.presets[presetIndex] || schema.presets[0]
    : null
  if (preset?.settings && typeof preset.settings === "object") {
    Object.assign(settings, preset.settings)
  }

  if (Array.isArray(preset?.blocks)) {
    const { parsedBlocks, parsedOrder } = parsePresetBlocks(
      preset.blocks,
      "",
      themeBlocks,
      (type, prefix, i) => `${type}_preview_${prefix}${i}`,
    )
    Object.assign(blocks, parsedBlocks)
    order.push(...parsedOrder)
  }

  return {
    section_id: `preview_${type}`,
    section_type: type,
    settings,
    blocks,
    order,
  }
}

function buildPreviewUrl(slug: string | null): string {
  if (!slug) return "about:blank"
  const url = new URL(api.getPreviewUrl(slug), window.location.origin)
  url.searchParams.set("pb-editor", "1")
  url.searchParams.set("pb-preview", "1")
  url.searchParams.set("source", "section")
  return url.toString()
}

export default function AddSectionModal() {
  const editor = useEditorInstance()
  const layout = useEditorLayout()
  const { slug } = useEditorNavigation()
  const { sections, blocks: themeBlocks } = useStore()
  const isOpen = layout.addSectionModal.isOpen

  const isMobile = !useBreakpoint(768)
  const iframeRef = useRef<HTMLIFrameElement | null>(null)
  const previewContainerRef = useRef<HTMLDivElement | null>(null)
  const [iframeReady, setIframeReady] = useState(false)
  const [previewScale, setPreviewScale] = useState(1)
  const [previewContainerHeight, setPreviewContainerHeight] = useState(0)
  const [query, setQuery] = useState("")
  const [selectedType, setSelectedType] = useState<string | null>(null)
  const [hoveredType, setHoveredType] = useState<string | null>(null)
  const hoverTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const [previewHtml, setPreviewHtml] = useState("")
  const [isPreviewLoading, setIsPreviewLoading] = useState(false)
  const [previewError, setPreviewError] = useState<string | null>(null)
  const [selectedPresetIndex, setSelectedPresetIndex] = useState(0)

  // Mobile-specific step logic
  const [step, setStep] = useState<"list" | "preview">("list")

  const entries = useMemo<SectionEntry[]>(() => {
    return Object.entries(sections).map(([type, meta]) => ({
      type,
      meta: (meta || {}) as Record<string, any>,
      label: meta?.schema?.name || meta?.name,
    }))
  }, [sections])

  const filteredEntries = useMemo(() => {
    const q = query.trim().toLowerCase()
    if (!q) return entries
    return entries.filter((entry) => {
      const haystack = `${entry.label} ${entry.type}`.toLowerCase()
      return haystack.includes(q)
    })
  }, [entries, query])

  const selectedEntry = useMemo(() => {
    const typeToFind = selectedType || hoveredType
    if (!typeToFind) return filteredEntries[0] || null
    return (
      filteredEntries.find((entry) => entry.type === typeToFind) ||
      entries.find((entry) => entry.type === typeToFind) ||
      null
    )
  }, [entries, filteredEntries, selectedType, hoveredType])

  const previewEntry = useMemo(() => {
    if (hoveredType) {
      return (
        filteredEntries.find((entry) => entry.type === hoveredType) ||
        entries.find((entry) => entry.type === hoveredType) ||
        null
      )
    }
    return selectedEntry
  }, [hoveredType, filteredEntries, entries, selectedEntry])

  useEffect(() => {
    setSelectedPresetIndex(0)
  }, [previewEntry?.type])

  const previewSrc = useMemo(() => buildPreviewUrl(slug), [slug])
  const iframeHeight = useMemo(() => {
    if (previewContainerHeight <= 0 || previewScale <= 0) {
      return "100%"
    }
    return `${previewContainerHeight / previewScale}px`
  }, [previewContainerHeight, previewScale])

  const updatePreviewScale = useCallback(() => {
    const container = previewContainerRef.current
    if (!container) return

    const containerWidth = container.clientWidth
    const containerHeight = container.clientHeight

    if (!containerWidth || !containerHeight) return

    const scale = containerWidth / PREVIEW_IFRAME_WIDTH

    setPreviewScale(scale)
    setPreviewContainerHeight(containerHeight)
  }, [])

  const sendPreviewHtml = useCallback(() => {
    if (!previewEntry) return
    const iframe = iframeRef.current
    if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return

    injectEditorScript(iframe.contentDocument, iframe.contentWindow)
    iframe.contentWindow.postMessage(
      {
        type: "set-inspector",
        enabled: false,
      },
      "*",
    )
    iframe.contentWindow.postMessage(
      {
        type: "set-preview-html",
        html: previewHtml,
      },
      "*",
    )
  }, [previewEntry, previewHtml])

  useEffect(() => {
    if (!isOpen) return
    setQuery("")
    setSelectedType(entries[0]?.type || null)
    setHoveredType(null)
    setPreviewError(null)
    setPreviewHtml("")
    setIframeReady(false)
    setStep("list")
  }, [isOpen, entries])

  useEffect(() => {
    if (!isOpen) return
    if (filteredEntries.length === 0) {
      setSelectedType(null)
      return
    }
    if (!selectedType || !filteredEntries.some((entry) => entry.type === selectedType)) {
      setSelectedType(filteredEntries[0].type)
    }
  }, [isOpen, filteredEntries, selectedType])

  useEffect(() => {
    if (!isOpen) return
    if (!previewEntry) {
      setPreviewHtml("")
      setPreviewError(null)
      return
    }

    let cancelled = false

    const loadPreview = async () => {
      setIsPreviewLoading(true)
      setPreviewError(null)
      try {
        const payload = {
          ...buildSectionPreviewPayload(
            previewEntry.type,
            previewEntry.meta,
            selectedPresetIndex,
            themeBlocks,
          ),
          slug,
        }
        const { html } = await api.renderSection(payload)
        if (cancelled) return
        setPreviewHtml(html || "")
      } catch {
        if (cancelled) return
        setPreviewError("Unable to render section preview.")
        setPreviewHtml("")
      } finally {
        if (!cancelled) setIsPreviewLoading(false)
      }
    }

    const timer = window.setTimeout(() => {
      void loadPreview()
    }, 80)

    return () => {
      cancelled = true
      window.clearTimeout(timer)
    }
  }, [isOpen, previewEntry?.type, selectedPresetIndex])

  useEffect(() => {
    if (!isOpen || !iframeReady) return
    sendPreviewHtml()
  }, [isOpen, iframeReady, sendPreviewHtml])

  useEffect(() => {
    if (!isOpen) return

    requestAnimationFrame(() => {
      updatePreviewScale()
    })
  }, [isOpen, updatePreviewScale])

  useEffect(() => {
    if (!isOpen) return

    const container = previewContainerRef.current
    if (!container) return

    const handleResize = () => {
      requestAnimationFrame(updatePreviewScale)
    }

    handleResize()

    let resizeObserver: ResizeObserver | null = null
    if ("ResizeObserver" in window) {
      resizeObserver = new ResizeObserver(handleResize)
      resizeObserver.observe(container)
    }

    window.addEventListener("resize", handleResize)
    return () => {
      resizeObserver?.disconnect()
      window.removeEventListener("resize", handleResize)
    }
  }, [isOpen, updatePreviewScale])

  const handleAddEntry = (entry: SectionEntry) => {
    const sectionId = editor.addSection(
      entry.type,
      entry.meta || {},
      layout.addSectionModal.insertIndex ?? null,
      selectedPresetIndex,
    )
    editor.layout.closeAddSectionModal()
    editor.selectSection(sectionId)
  }

  const handleEntryClick = (entry: SectionEntry) => {
    if (isMobile) {
      setSelectedType(entry.type)
      setStep("preview")
    } else {
      handleAddEntry(entry)
    }
  }

  const renderList = () => (
    <aside
      className={cn(
        "flex w-64 shrink-0 flex-col border-r border-gray-200 bg-white",
        isMobile && "w-full border-r-0 pb-6",
      )}
    >
      <div className="border-b border-gray-200 bg-white px-3 py-3">
        {!isMobile && <p className="mb-2 text-sm font-semibold text-gray-900">Add section</p>}
        <div className="relative">
          <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
          <input
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search sections"
            className="h-9 w-full rounded-md border border-gray-300 bg-white pr-2 pl-9 text-sm text-gray-800 outline-none focus:border-blue-500"
          />
        </div>
      </div>

      <div className="flex-1 overflow-y-auto p-2">
        {filteredEntries.length === 0 && (
          <p className="px-2 py-6 text-center text-xs text-gray-500">No sections found.</p>
        )}

        {filteredEntries.map((entry) => {
          const active = previewEntry?.type === entry.type
          return (
            <button
              key={entry.type}
              onMouseEnter={() => {
                if (isMobile) return
                if (hoverTimeoutRef.current) clearTimeout(hoverTimeoutRef.current)
                hoverTimeoutRef.current = setTimeout(() => {
                  setHoveredType(entry.type)
                  setSelectedType(entry.type)
                }, 100)
              }}
              onMouseLeave={() => {
                if (isMobile) return
                if (hoverTimeoutRef.current) clearTimeout(hoverTimeoutRef.current)
                setHoveredType(null)
              }}
              onFocus={() => setSelectedType(entry.type)}
              onClick={() => handleEntryClick(entry)}
              className={cn(
                "group mb-1 flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm transition",
                active && !isMobile ? "bg-gray-900 text-white" : "text-gray-700 hover:bg-gray-200",
                isMobile && "border-b border-gray-50 py-3",
              )}
            >
              <Layout className="h-4 w-4 shrink-0" />
              <span className="flex-1 truncate font-medium">{entry.label}</span>
              {isMobile && <ArrowRight className="h-4 w-4 text-gray-300" />}
            </button>
          )
        })}
      </div>
    </aside>
  )

  const renderPreview = () => (
    <section
      className={cn(
        "flex min-w-0 flex-1 flex-col bg-gray-50 p-3",
        isMobile && "h-[60vh] bg-white p-0",
      )}
    >
      {isMobile && (
        <div className="flex shrink-0 items-center justify-between border-b bg-white px-3 py-3">
          <button
            onClick={() => setStep("list")}
            className="flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900"
          >
            <ChevronLeft className="h-4 w-4" />
            Back
          </button>
          <span className="text-sm font-bold text-gray-900">{previewEntry?.label}</span>
          <button
            onClick={() => previewEntry && handleAddEntry(previewEntry)}
            className="text-sm font-bold text-blue-600 hover:text-blue-700"
          >
            Add
          </button>
        </div>
      )}

      {previewEntry &&
        Array.isArray(previewEntry.meta?.schema?.presets) &&
        previewEntry.meta.schema.presets.length > 1 && (
          <div className="flex shrink-0 items-center border-b border-gray-200 bg-white px-3 py-2">
            <span className="mr-3 text-sm font-medium text-gray-500">Preset:</span>
            <select
              value={selectedPresetIndex}
              onChange={(e) => setSelectedPresetIndex(Number(e.target.value))}
              className="min-w-[200px] rounded border border-gray-300 bg-white px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
            >
              {previewEntry.meta.schema.presets.map((preset: any, idx: number) => (
                <option key={idx} value={idx}>
                  {preset.name || `Preset ${idx + 1}`}
                </option>
              ))}
            </select>
          </div>
        )}

      <div className="relative flex flex-1 items-center justify-center overflow-hidden p-2">
        {isPreviewLoading && (
          <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/50 text-sm text-gray-500">
            Rendering preview...
          </div>
        )}

        {previewError && !isPreviewLoading && (
          <div className="absolute top-2 right-2 left-2 z-10 bg-amber-50 px-2.5 py-1.5 text-center text-xs text-amber-700">
            {previewError}
          </div>
        )}

        <div
          ref={previewContainerRef}
          className="relative aspect-[16/10] max-h-full w-full max-w-[1120px] min-w-0 overflow-hidden"
          style={{ maxWidth: "100%" }}
        >
          <iframe
            ref={iframeRef}
            key={previewSrc}
            title="Section preview"
            src={previewSrc}
            className="block"
            style={{
              position: "absolute",
              top: 0,
              left: 0,
              width: `${PREVIEW_IFRAME_WIDTH}px`,
              height: iframeHeight,
              transform: `scale(${previewScale})`,
              transformOrigin: "top left",
              border: "0",
              pointerEvents: "none",
            }}
            sandbox="allow-same-origin allow-scripts"
            onLoad={() => {
              setIframeReady(true)
              sendPreviewHtml()
            }}
          />
        </div>
      </div>
    </section>
  )

  if (isMobile) {
    return (
      <Drawer open={isOpen} onOpenChange={(open) => !open && editor.layout.closeAddSectionModal()}>
        <DrawerContent
          hideOverlay={true}
          className="flex max-h-[60vh] flex-col overflow-hidden p-0"
        >
          <DrawerHeader>
            <DrawerTitle className="text-center text-base font-bold">
              {step === "list" ? "Add Section" : "Preview Section"}
            </DrawerTitle>
          </DrawerHeader>

          <div className="flex flex-1 flex-col overflow-hidden">
            {step === "list" ? renderList() : renderPreview()}
          </div>
        </DrawerContent>
      </Drawer>
    )
  }

  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && editor.layout.closeAddSectionModal()}>
      <DialogContent className="max-w-[650px] gap-0 overflow-hidden p-0">
        <DialogHeader className="sr-only">
          <DialogTitle>Add Section</DialogTitle>
        </DialogHeader>

        <div className="flex h-[70vh] min-h-[480px]">
          {renderList()}
          {renderPreview()}
        </div>
      </DialogContent>
    </Dialog>
  )
}
