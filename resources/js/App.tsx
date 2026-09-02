import React from "react"
import { useEditor } from "./hooks/useEditor"
import { useBreakpoint } from "./hooks/useBreakpoint"
import { useMobileDrawerAutoOpen } from "./hooks/useMobileDrawerAutoOpen"
import { useEditorLayout } from "./hooks/useEditorLayout"
import EditorHeader from "./components/EditorHeader"
import EditorSidebar from "./components/EditorSidebar"
import SettingsSidebar from "./components/SettingsSidebar"
import PreviewCanvas from "./components/PreviewCanvas"
import AddSectionModal from "./components/AddSectionModal"
import MobileDock from "./components/mobile/MobileDock"
import MobileDrawer from "./components/mobile/MobileDrawer"

/**
 * Root editor component — pure composition with zero prop drilling.
 *
 * useEditor() runs root side-effects (bootstrap, history, shortcuts).
 * Every child component reads its own data directly from the editor
 * context, manager hooks, and Zustand store.
 */
export default function App() {
  useEditor()
  const layout = useEditorLayout()

  /** True on tablets and desktops; false on phones. */
  const isDesktop = useBreakpoint(768)

  // Auto-open the "sections" drawer when a section/block is selected on mobile.
  useMobileDrawerAutoOpen(!isDesktop)

  return (
    <div className="flex h-screen flex-col overflow-hidden bg-gray-100">
      <EditorHeader />

      <div className="flex flex-1 overflow-hidden">
        {/* Left sidebar — desktop only, hidden in fullscreen mode */}
        {isDesktop && !layout.isFullscreen && <EditorSidebar />}

        <PreviewCanvas />

        {/* Right sidebar — visible only on large screens */}
        {isDesktop && layout.isDualSidebar && <SettingsSidebar />}
      </div>

      {/* Mobile bottom dock — shown only on small screens */}
      {!isDesktop && <MobileDock />}

      <AddSectionModal />

      {/* Mobile bottom drawer — shown only on small screens */}
      {!isDesktop && <MobileDrawer />}
    </div>
  )
}
