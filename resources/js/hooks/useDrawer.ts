import { useMemo, useSyncExternalStore } from "react"
import { drawerManager, type MobileDrawerPanel } from "@/core/editor/DrawerManager"

/**
 * React adapter for the DrawerManager singleton.
 *
 * Subscribes to drawer state changes via useSyncExternalStore so any
 * component using this hook re-renders whenever the active panel changes.
 *
 * Returns the current snapshot plus bound action helpers so callers
 * never need to import drawerManager directly.
 */
export function useDrawer() {
  const version = useSyncExternalStore(
    (listener) => drawerManager.subscribe(listener),
    () => drawerManager.getVersion(),
    () => 0,
  )

  return useMemo(
    () => ({
      ...drawerManager.getSnapshot(),
      open: (panel: MobileDrawerPanel) => drawerManager.open(panel),
      close: () => drawerManager.close(),
      toggle: (panel: MobileDrawerPanel) => drawerManager.toggle(panel),
    }),
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [version],
  )
}
