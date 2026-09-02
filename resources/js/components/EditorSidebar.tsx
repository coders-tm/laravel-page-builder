import React, { memo, useState } from "react"
import VerticalTabStrip from "./layout/VerticalTabStrip"
import LayoutPanel from "./LayoutPanel"
import SettingsPanel from "./SettingsPanel"
import PageMetaPanel from "./PageMetaPanel"
import ThemeSettingsPanel from "./ThemeSettingsPanel"
import InfoModal from "./InfoModal"
import { SidebarSkeleton } from "./ui/SidebarSkeleton"
import { useEditorLayout } from "@/hooks/useEditorLayout"
import { useEditorNavigation } from "@/hooks/useEditorNavigation"
import { useEditorInstance } from "@/core/editorContext"
import { useStore } from "@/core/store/useStore"

/**
 * Left sidebar of the editor containing the vertical tab strip
 * and the active panel content.
 */
function EditorSidebar() {
  const editor = useEditorInstance()
  const { loading } = useStore()
  const layout = useEditorLayout()
  const { selectedSection } = useEditorNavigation()
  const [infoOpen, setInfoOpen] = useState(false)

  const showSettings = !!selectedSection

  const renderPanelContent = () => {
    if (loading) return <SidebarSkeleton />

    if (layout.sidebarTab === "page") {
      return <PageMetaPanel />
    }

    if (layout.sidebarTab === "theme") {
      return <ThemeSettingsPanel />
    }

    // "sections" tab: dual-sidebar always shows LayoutPanel;
    // single-sidebar toggles based on selection state.
    if (layout.isDualSidebar || !showSettings || layout.addBlockModal.isOpen) {
      return <LayoutPanel />
    }

    return <SettingsPanel />
  }

  return (
    <div className="flex shrink-0 overflow-hidden border-r border-gray-200 bg-white shadow-sm">
      <VerticalTabStrip
        activeTab={layout.sidebarTab}
        onTabChange={(tab) => editor.layout.setSidebarTab(tab)}
        onInfoClick={() => setInfoOpen(true)}
      />
      <div className="flex w-72 max-w-[320px] min-w-[256px] flex-col overflow-hidden">
        {renderPanelContent()}
      </div>
      <InfoModal isOpen={infoOpen} onClose={() => setInfoOpen(false)} />
    </div>
  )
}

export default memo(EditorSidebar)
