import React, { memo } from "react"
import { Layers, FileText, Palette, Info } from "lucide-react"
import { cn } from "@/lib/utils"
import type { SidebarTab } from "@/hooks/useEditorLayout"
import config from "@/config"

interface TabItem {
  id: SidebarTab
  label: string
  icon: React.ReactNode
}

const TABS: TabItem[] = [
  { id: "sections", label: "Sections", icon: <Layers size={15} /> },
  { id: "page", label: "Page", icon: <FileText size={15} /> },
  { id: "theme", label: "Theme", icon: <Palette size={15} /> },
]

interface VerticalTabStripProps {
  activeTab: SidebarTab
  onTabChange: (tab: SidebarTab) => void
  onInfoClick: () => void
}

/**
 * Vertical icon tab strip rendered on the far-left edge of the sidebar.
 * Switches between Sections, Page meta, and Theme settings panels.
 * Includes an info button at the bottom that opens the about modal.
 */
function VerticalTabStrip({ activeTab, onTabChange, onInfoClick }: VerticalTabStripProps) {
  if (config.mode === "email") return null
  return (
    <div className="flex shrink-0 flex-col items-center gap-1 border-r border-gray-100 px-1 py-3">
      {TABS.map((tab) => (
        <button
          key={tab.id}
          type="button"
          title={tab.label}
          onClick={() => onTabChange(tab.id)}
          className={cn(
            "flex h-10 w-10 items-center justify-center rounded-lg p-2.5 transition-colors",
            activeTab === tab.id
              ? "bg-gray-200 text-gray-800"
              : "text-gray-400 hover:bg-gray-100 hover:text-gray-600",
          )}
        >
          {tab.icon}
        </button>
      ))}
      <button
        type="button"
        title="About Laravel Page Builder"
        onClick={onInfoClick}
        className="flex h-10 w-10 items-center justify-center rounded-lg p-2.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
      >
        <Info size={15} />
      </button>
    </div>
  )
}

export default memo(VerticalTabStrip)
