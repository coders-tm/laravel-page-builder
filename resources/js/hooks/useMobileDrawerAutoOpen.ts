import { useEffect, useMemo, useSyncExternalStore } from "react"
import { useEditorInstance } from "@/core/editorContext"
import { drawerManager } from "@/core/editor/DrawerManager"

/**
 * Automatically opens the "sections" drawer when a section or block becomes
 * selected on mobile (e.g. by tapping in the canvas or clicking a row in
 * the Layers panel).
 *
 * The "sections" panel is unified: it shows SettingsPanel when something is
 * selected and LayoutPanel when nothing is selected — mirroring the desktop
 * single-sidebar behaviour.
 *
 * Reads from `editor.navigation` directly (via useSyncExternalStore) rather
 * than calling useEditorNavigation, so no duplicate router-adapter side
 * effects are introduced.
 *
 * @param isMobile - When false the hook is a no-op; only fires on small screens.
 */
export function useMobileDrawerAutoOpen(isMobile: boolean): void {
  const editor = useEditorInstance()

  const version = useSyncExternalStore(
    (cb) => editor.navigation.subscribe(cb),
    () => editor.navigation.getVersion(),
    () => 0,
  )

  const { selectedSection, blockPath } = useMemo(
    () => editor.navigation.getSnapshot(),
    [editor, version],
  )

  const blockPathKey = blockPath.join(",")

  useEffect(() => {
    if (!isMobile) return
    if (selectedSection || blockPathKey) {
      // Open the unified "sections" panel — it auto-switches to
      // SettingsPanel when a selection is active.
      drawerManager.open("sections")
    }
  }, [isMobile, selectedSection, blockPathKey])
}
