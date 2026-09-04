/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { memo, useState } from "react"
import { Layers, FileText, Palette, Info } from "lucide-react"
import { cn } from "@/lib/utils"
import { useDrawer } from "@/hooks/useDrawer"
import InfoModal from "@/components/InfoModal"
import type { MobileDrawerPanel } from "@/core/editor/DrawerManager"

interface DockItem {
  id: MobileDrawerPanel
  label: string
  icon: React.ReactNode
}

const DOCK_ITEMS: DockItem[] = [
  { id: "sections", label: "Layers", icon: <Layers size={20} /> },
  { id: "page", label: "Pages", icon: <FileText size={20} /> },
  { id: "theme", label: "Theme", icon: <Palette size={20} /> },
]

/**
 * MobileDock — bottom dock bar for the mobile editor.
 *
 * Renders a row of icon-label buttons, one per panel. Tapping a button
 * toggles the corresponding panel open/closed in the MobileDrawer.
 * The active panel button is highlighted in blue.
 *
 * Includes an info button that opens the about modal.
 *
 * Only rendered on small screens (controlled by the parent).
 */
function MobileDock() {
  const { activePanel, toggle } = useDrawer()
  const [infoOpen, setInfoOpen] = useState(false)

  return (
    <>
      <div className="flex shrink-0 items-center justify-around border-t border-gray-200 bg-white px-2 py-1">
        {DOCK_ITEMS.map((item) => (
          <button
            key={item.id}
            type="button"
            title={item.label}
            onClick={() => toggle(item.id)}
            className={cn(
              "flex flex-1 flex-col items-center gap-0.5 rounded-lg py-2 text-xs font-medium transition-colors",
              activePanel === item.id ? "text-blue-600" : "text-gray-500 hover:text-gray-700",
            )}
          >
            {item.icon}
            <span>{item.label}</span>
          </button>
        ))}

        <button
          type="button"
          title="About"
          onClick={() => setInfoOpen(true)}
          className="flex flex-1 flex-col items-center gap-0.5 rounded-lg py-2 text-xs font-medium text-gray-500 transition-colors hover:text-gray-700"
        >
          <Info size={20} />
          <span>About</span>
        </button>
      </div>

      <InfoModal isOpen={infoOpen} onClose={() => setInfoOpen(false)} />
    </>
  )
}

export default memo(MobileDock)
