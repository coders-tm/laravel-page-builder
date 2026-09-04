/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { memo, useEffect, useRef } from "react"
import { X } from "lucide-react"
import { Drawer, DrawerContent, DrawerHeader, DrawerTitle } from "@/components/ui/drawer"
import LayoutPanel from "@/components/LayoutPanel"
import SettingsPanel from "@/components/SettingsPanel"
import PageMetaPanel from "@/components/PageMetaPanel"
import ThemeSettingsPanel from "@/components/ThemeSettingsPanel"
import { SidebarSkeleton } from "@/components/ui/SidebarSkeleton"
import { useDrawer } from "@/hooks/useDrawer"
import { useEditorNavigation } from "@/hooks/useEditorNavigation"
import { useStore } from "@/core/store/useStore"
import type { MobileDrawerPanel } from "@/core/editor/DrawerManager"

const PANEL_LABELS: Record<MobileDrawerPanel, string> = {
  sections: "Layers",
  page: "Pages",
  theme: "Theme",
}

/**
 * MobileDrawer — bottom sheet for the mobile editor.
 * All data is read directly from the store and editor context.
 */
function MobileDrawer() {
  const { activePanel, isOpen, close } = useDrawer()
  const { loading } = useStore()

  // Read selection state without registering the router adapter
  // (only the root-level useEditor hook may own the adapter).
  const { selectedSection } = useEditorNavigation({ registerAdapter: false })

  const isSectionsSettingsMode = activePanel === "sections" && !!selectedSection && !loading

  // vaul sets aria-hidden="true" on #editor (the app root) whenever the
  // drawer opens/animates. Strip it immediately so the canvas stays interactive.
  useEffect(() => {
    const root = document.getElementById("editor")
    if (!root) return

    const observer = new MutationObserver(() => {
      if (root.hasAttribute("aria-hidden")) {
        root.removeAttribute("aria-hidden")
        root.removeAttribute("data-aria-hidden")
      }
    })

    observer.observe(root, {
      attributes: true,
      attributeFilter: ["aria-hidden", "data-aria-hidden"],
    })

    return () => observer.disconnect()
  }, [])

  const renderPanel = () => {
    if (loading) return <SidebarSkeleton />

    switch (activePanel) {
      case "sections":
        return selectedSection ? <SettingsPanel /> : <LayoutPanel />
      case "page":
        return <PageMetaPanel />
      case "theme":
        return <ThemeSettingsPanel />
      default:
        return null
    }
  }

  const drawerRef = useRef<HTMLDivElement | null>(null)

  useEffect(() => {
    if (!isOpen) return

    function onPointerDown(e: PointerEvent) {
      const target = e.target as Node | null
      if (!drawerRef.current) return
      if (target && !drawerRef.current.contains(target)) {
        close()
      }
    }

    document.addEventListener("pointerdown", onPointerDown)
    return () => document.removeEventListener("pointerdown", onPointerDown)
  }, [isOpen, close])

  return (
    <Drawer
      open={isOpen}
      onOpenChange={(open) => {
        if (!open) close()
      }}
      shouldScaleBackground={false}
      modal={false}
    >
      <DrawerContent hideOverlay>
        <div ref={drawerRef} className="flex h-[60vh] flex-col">
          {isSectionsSettingsMode ? (
            <DrawerTitle className="sr-only">Layers</DrawerTitle>
          ) : (
            <DrawerHeader className="flex shrink-0 items-center justify-between border-b border-gray-100 px-4 py-3">
              <DrawerTitle className="text-sm font-semibold text-gray-800">
                {activePanel ? PANEL_LABELS[activePanel] : ""}
              </DrawerTitle>
              <button
                type="button"
                onClick={close}
                aria-label="Close panel"
                className="rounded-md p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
              >
                <X size={18} />
              </button>
            </DrawerHeader>
          )}

          <div className="flex-1 overflow-y-auto">{renderPanel()}</div>
        </div>
      </DrawerContent>
    </Drawer>
  )
}

export default memo(MobileDrawer)
