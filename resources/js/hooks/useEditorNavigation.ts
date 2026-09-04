/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { useEffect, useMemo, useSyncExternalStore } from "react"
import { useNavigate, useSearchParams, useLocation } from "react-router-dom"
import { useEditorInstance } from "@/core/editorContext"

interface UseEditorNavigationOptions {
  /**
   * When true (default), this hook instance registers the React Router
   * adapter on the NavigationManager so that navigation commands
   * (setSection, setDevice, etc.) can update the URL via setSearchParams.
   *
   * Set to false in any component that may unmount while the editor is still
   * active (e.g. panels rendered inside the mobile drawer). Having multiple
   * registrants compete causes the adapter to be nulled when a panel
   * unmounts, breaking URL updates in PreviewCanvas.
   *
   * Only the root-level useEditor hook should use registerAdapter: true.
   */
  registerAdapter?: boolean
}

/**
 * React Router bridge for the class-based NavigationManager.
 *
 * Parses current URL state, syncs it into `editor.navigation`, and exposes
 * the manager snapshot + commands to components.
 */
export function useEditorNavigation({ registerAdapter = false }: UseEditorNavigationOptions = {}) {
  const editor = useEditorInstance()

  const navigate = useNavigate()
  const location = useLocation()
  const rawPath = location.pathname || "/"
  const slug = rawPath === "/" ? "home" : rawPath.replace(/^\//, "")
  const [searchParams, setSearchParams] = useSearchParams()

  const selectedSection = searchParams.get("section") || null
  const device = searchParams.get("device") || "desktop"
  const lang = searchParams.get("lang") || null
  const rawBlock = searchParams.get("block") || ""
  const blockPath: string[] = rawBlock ? rawBlock.split(",") : []
  const isEditorMode = searchParams.get("editor") === "true"

  // Register router adapter for URL updates originating from manager commands.
  // Only the root-level caller (useEditor) opts in via registerAdapter: true.
  // Child panel components must NOT register the adapter — their unmount
  // cleanup would null it out, breaking navigation in PreviewCanvas.
  useEffect(() => {
    if (!registerAdapter) return
    editor.navigation.setAdapter({
      navigate,
      setSearchParams,
      getSearchParams: () => searchParams,
      editorMode: isEditorMode,
    })

    return () => {
      editor.navigation.setAdapter(null)
    }
  }, [editor, navigate, setSearchParams, registerAdapter, isEditorMode])

  // Sync current route into the manager.
  useEffect(() => {
    editor.navigation.syncFromRoute({
      slug,
      device,
      lang,
      selectedSection,
      blockPath,
    })
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [editor, slug, device, lang, selectedSection, blockPath.join(",")])

  const version = useSyncExternalStore(
    (listener) => editor.navigation.subscribe(listener),
    () => editor.navigation.getVersion(),
    () => 0,
  )

  return useMemo(
    () => ({
      ...editor.navigation.getSnapshot(),
      setPage: (nextSlug: string) => editor.navigation.setPage(nextSlug),
      setLang: (nextLang: string | null) => editor.navigation.setLang(nextLang),
      setSelection: (sectionId: string | null, path: string[] = []) =>
        editor.navigation.setSelection(sectionId, path),
      setSection: (sectionId: string | null, block: string | string[] | null = null) =>
        editor.navigation.setSection(sectionId, block),
      pushBlock: (blockId: string) => editor.navigation.pushBlock(blockId),
      clearSection: () => editor.navigation.clearSelection(),
      setDevice: (nextDevice: string) => editor.navigation.setDevice(nextDevice),
      goBack: () => editor.navigation.goBack(),
      navigate,
    }),
    [editor, navigate, version],
  )
}
