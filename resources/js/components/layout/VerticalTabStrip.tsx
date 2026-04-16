import React, { memo } from "react";
import { Layers, FileText, Palette } from "lucide-react";
import { cn } from "@/lib/utils";
import type { SidebarTab } from "@/hooks/useEditorLayout";
import config from "@/config";

interface TabItem {
  id: SidebarTab;
  label: string;
  icon: React.ReactNode;
}

const TABS: TabItem[] = [
  { id: "sections", label: "Sections", icon: <Layers size={15} /> },
  { id: "page", label: "Page", icon: <FileText size={15} /> },
  { id: "theme", label: "Theme", icon: <Palette size={15} /> },
];

interface VerticalTabStripProps {
  activeTab: SidebarTab;
  onTabChange: (tab: SidebarTab) => void;
}

/**
 * Vertical icon tab strip rendered on the far-left edge of the sidebar.
 * Switches between Sections, Page meta, and Theme settings panels.
 */
function VerticalTabStrip({ activeTab, onTabChange }: VerticalTabStripProps) {
  if (config.mode === "email") return null;
  return (
    <div className="flex flex-col items-center gap-1 py-3 px-1 border-r border-gray-100 shrink-0">
      {TABS.map((tab) => (
        <button
          key={tab.id}
          type="button"
          title={tab.label}
          onClick={() => onTabChange(tab.id)}
          className={cn(
            "flex items-center justify-center p-2.5 rounded-lg transition-colors w-10 h-10",
            activeTab === tab.id
              ? "bg-gray-200 text-gray-800"
              : "text-gray-400 hover:bg-gray-100 hover:text-gray-600",
          )}
        >
          {tab.icon}
        </button>
      ))}
    </div>
  );
}

export default memo(VerticalTabStrip);
