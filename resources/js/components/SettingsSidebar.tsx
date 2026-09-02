import React, { memo } from "react"
import SettingsPanel from "./SettingsPanel"
import { SidebarSkeleton } from "./ui/SidebarSkeleton"
import { useStore } from "@/core/store/useStore"

/**
 * Right sidebar shown only on large screens (dual-sidebar layout).
 * Displays section/block settings or a placeholder when nothing is selected.
 */
function SettingsSidebar() {
  const { loading } = useStore()

  return (
    <div className="flex w-80 max-w-[360px] min-w-[280px] shrink-0 flex-col overflow-hidden border-l border-gray-200 bg-white shadow-sm">
      {loading ? <SidebarSkeleton /> : <SettingsPanel />}
    </div>
  )
}

export default memo(SettingsSidebar)
