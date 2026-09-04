/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useEffect, useRef, useCallback, useMemo, useState } from "react"
import api from "../services/api"
import config from "../config"
import { Loader2, Layout } from "lucide-react"
import { LoadingBar } from "./ui/LoadingBar"
import { injectEditorScript } from "../core/messaging/EditorScriptInjector"
import { useEditorInstance } from "@/core/editorContext"
import { useEditorNavigation } from "@/hooks/useEditorNavigation"
import { useEditorLayout } from "@/hooks/useEditorLayout"
import { useStore } from "@/core/store/useStore"

// Scale factor applied to the preview canvas during drag operations.
const DRAG_SCALE = 0.6

/**
 * Preview iframe — reads all state from editor context and manager
 * hooks. Owns the iframeRef and wires it into the PreviewManager and
 * InteractionManager on mount.
 */
export default function PreviewCanvas() {
  const editor = useEditorInstance()
  const { slug, device, selectedSection, selectedBlock } = useEditorNavigation()
  const { inspectorEnabled, isDraggingLayout: isDragging } = useEditorLayout()
  const { saving } = useStore()

  const iframeRef = useRef<HTMLIFrameElement>(null)
  const debounceTimer = useRef<ReturnType<typeof setTimeout> | null>(null)
  const [iframeLoaded, setIframeLoaded] = useState(false)
  const [isReloading, setIsReloading] = useState(false)

  // Tracks the height of the scroll container for drag-scale compensation.
  const canvasRef = useRef<HTMLDivElement>(null)
  const [canvasHeight, setCanvasHeight] = useState(0)

  useEffect(() => {
    const el = canvasRef.current
    if (!el) return
    const ro = new ResizeObserver(([entry]) => {
      setCanvasHeight(entry.contentRect.height)
    })
    ro.observe(el)
    return () => ro.disconnect()
  }, [])

  // Create and wire the message bus on mount.
  const messageBus = useMemo(() => editor.interaction.createMessageBus(iframeRef), [editor])

  useEffect(() => {
    editor.preview.setMessageBus(messageBus)
    editor.interaction.setMessageBus(messageBus)
  }, [editor, messageBus])

  /* ── Device width mapping ─────────────────────────────────────────── */
  const deviceWidths: Record<string, string> = {
    desktop: "100%",
    tablet: "768px",
    mobile: "390px",
    fullscreen: "100%",
  }
  const deviceWidth = deviceWidths[device] || "100%"

  /* ── Send a postMessage to the iframe ────────────────────────────── */
  const sendMessage = useCallback(
    (data: Record<string, any>) => {
      iframeRef.current?.contentWindow?.postMessage(data, "*")
    },
    [iframeRef],
  )

  /* ── Highlight a section in the preview ─────────────────────────── */
  const highlightSection = useCallback(
    (sectionId: string, blockId: string | null = null) => {
      sendMessage({ type: "highlight-section", sectionId, blockId })
    },
    [sendMessage],
  )

  /* ── Clear all highlights ────────────────────────────────────────── */
  const clearHighlight = useCallback(() => {
    sendMessage({ type: "clear-selection" })
  }, [sendMessage])

  /* ── Scroll iframe to section ────────────────────────────────────── */
  const scrollToSection = useCallback(
    (sectionId: string) => {
      sendMessage({ type: "scroll-to-section", sectionId })
    },
    [sendMessage],
  )

  /* ── Sync highlight when selection changes ───────────────────────── */
  useEffect(() => {
    if (selectedSection) {
      highlightSection(selectedSection, selectedBlock)
      scrollToSection(selectedSection)
    } else {
      clearHighlight()
    }
  }, [selectedSection, selectedBlock, highlightSection, clearHighlight, scrollToSection])

  /* ── Listen for messages from the iframe ─────────────────────────── */
  useEffect(() => {
    const handleMessage = (event: MessageEvent) => {
      const { type, sectionId, blockId } = event.data || {}
      switch (type) {
        case "preview-ready":
          if (selectedSection) {
            setTimeout(() => {
              highlightSection(selectedSection, selectedBlock)
            }, 100)
          }
          sendMessage({
            type: "set-inspector",
            enabled: inspectorEnabled,
          })
          setIsReloading(false)
          break

        case "section-selected":
          if (inspectorEnabled && sectionId) {
            editor.selectSection(sectionId)
            if (event.data.focusSetting) {
              setTimeout(() => editor.interaction.focusSetting(event.data.focusSetting), 100)
            }
          }
          break

        case "block-selected":
          if (inspectorEnabled && sectionId && blockId) {
            const path = event.data.path
            if (path) {
              const parsed = String(path).split(",").filter(Boolean)
              if (parsed.length > 0) {
                editor.selectBlock(sectionId, parsed)
              } else {
                editor.selectSection(sectionId)
              }
            } else if (event.data.id) {
              const parsed = String(event.data.id).split(",").filter(Boolean)
              if (parsed.length > 0) {
                editor.selectBlock(sectionId, parsed)
              } else {
                editor.selectSection(sectionId)
              }
            }
            if (event.data.focusSetting) {
              setTimeout(() => editor.interaction.focusSetting(event.data.focusSetting), 50)
            }
          }
          break

        case "add-section":
          if (inspectorEnabled) {
            editor.layout.openAddSectionModal(event.data.position, event.data.targetId)
          }
          break

        case "add-block":
          if (inspectorEnabled) {
            editor.addBlockFromPreview({
              position: event.data.position,
              sectionId: event.data.sectionId,
              targetId: event.data.targetId,
              parentPath: event.data.parentPath || [],
            })
          }
          break

        default:
          break
      }
    }

    window.addEventListener("message", handleMessage)
    return () => window.removeEventListener("message", handleMessage)
  }, [selectedSection, selectedBlock, inspectorEnabled, editor, highlightSection, sendMessage])

  /* ── Send inspector state update whenever it changes ─────────────── */
  useEffect(() => {
    // Hide inspector while dragging to prevent visual clutter and awkward interactions
    const actuallyEnabled = inspectorEnabled && !isDragging
    sendMessage({ type: "set-inspector", enabled: actuallyEnabled })
  }, [inspectorEnabled, isDragging, sendMessage])

  /* ── Inject Editor JS/CSS into iframe on load ────────────────────── */
  const handleIframeLoad = useCallback(() => {
    setIframeLoaded(true)
    if (iframeRef.current && iframeRef.current.contentWindow && iframeRef.current.contentDocument) {
      injectEditorScript(iframeRef.current.contentDocument, iframeRef.current.contentWindow)
    }
  }, [iframeRef])

  /* ── Preview URL ─────────────────────────────────────────────────── */
  const previewUrl = slug ? api.getPreviewUrl(slug) : null

  /* ── Live URL ────────────────────────────────────────────────────── */
  const liveUrl = useMemo(() => {
    if (!slug) return null
    const base = config.basePath === "/" ? "" : config.basePath
    const path = slug === "home" ? "" : `/${slug}`
    return base + path || "/"
  }, [slug])

  return (
    <div className="relative flex flex-1 flex-col overflow-hidden bg-gray-100">
      <LoadingBar isProcessing={saving || isReloading} />
      <div ref={canvasRef} className="flex flex-1 justify-center overflow-hidden p-0 md:p-2">
        <div
          className="shrink-0 transition-transform duration-300 ease-in-out"
          style={
            isDragging
              ? {
                  transform: `scale(${DRAG_SCALE})`,
                  transformOrigin: "top center",
                  width: deviceWidth,
                }
              : {
                  width: deviceWidth,
                }
          }
        >
          <div
            className="overflow-hidden bg-white transition-all duration-300 ease-in-out"
            style={
              isDragging && canvasHeight > 0
                ? {
                    width: "100%",
                    height: `${canvasHeight / DRAG_SCALE}px`,
                  }
                : {
                    width: "100%",
                    minHeight: "100%",
                  }
            }
          >
            {previewUrl ? (
              <div className="relative h-full w-full">
                <iframe
                  id="pb-preview-iframe"
                  name="pb-preview-iframe"
                  ref={iframeRef}
                  src={previewUrl}
                  onLoad={handleIframeLoad}
                  className="preview-iframe h-full w-full"
                  style={isDragging ? { pointerEvents: "none" } : {}}
                  title="Page Preview"
                  sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                  allow="clipboard-read; clipboard-write; local-network-access;"
                />
                {isReloading && (
                  <div className="absolute inset-0 z-10 flex items-center justify-center bg-white/60 backdrop-blur-[1px] transition-all duration-300">
                    <div className="flex flex-col items-center">
                      <Loader2 className="mb-2 h-8 w-8 animate-spin text-blue-500" />
                      <span className="rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-gray-700 shadow-sm">
                        Refreshing layout...
                      </span>
                    </div>
                  </div>
                )}
              </div>
            ) : (
              <div className="flex h-full min-h-[400px] items-center justify-center">
                <div className="text-center text-gray-400">
                  <Layout className="mx-auto mb-4 h-16 w-16 text-gray-300" strokeWidth={1.5} />
                  <p className="text-sm font-medium">No page selected</p>
                  <p className="mt-1 text-xs">Select a page from the header to start editing</p>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
