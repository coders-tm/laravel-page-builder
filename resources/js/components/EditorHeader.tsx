/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useCallback, useSyncExternalStore } from "react"
import { DeviceSwitcher, UndoRedoControls, EditorLogo, LanguageSelector } from "./header"
import { Crosshair, LogOut } from "lucide-react"
import { cn } from "@/lib/utils"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "./ui/select"
import { useEditorInstance } from "@/core/editorContext"
import { useEditorNavigation } from "@/hooks/useEditorNavigation"
import { useEditorLayout } from "@/hooks/useEditorLayout"
import { useStore } from "@/core/store/useStore"
import { useMaxBreakpoint } from "@/hooks/useBreakpoint"
import config from "@/config"

/**
 * Top header bar — reads all state directly from the editor context,
 * store, and manager hooks. No props required.
 */
export default function EditorHeader() {
  const editor = useEditorInstance()
  const { pages, saving } = useStore()
  const { slug, device, lang, setPage, setLang, setDevice } = useEditorNavigation()
  const { inspectorEnabled } = useEditorLayout()
  const isMobile = useMaxBreakpoint(768)

  // Subscribe to history manager for reactive canUndo / canRedo.
  useSyncExternalStore(
    useCallback((listener) => editor.history.subscribe(listener), [editor]),
    useCallback(() => editor.history.getVersion(), [editor]),
    () => 0,
  )
  const canUndo = editor.history.canUndo
  const canRedo = editor.history.canRedo

  return (
    <header className="z-50 flex h-[52px] flex-shrink-0 items-center gap-1 border-b border-gray-200 bg-white px-3">
      {/* ── Left: Logo + Page selector ─────────────────────────── */}
      <div className="flex flex-shrink-0 items-center gap-1.5">
        <button
          type="button"
          onClick={() => {
            window.dispatchEvent(new CustomEvent("pagebuilder:exit"))
          }}
          className="rotate-180 rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100"
          title="Exit Editor"
        >
          <LogOut className="h-[18px] w-[18px]" />
        </button>

        <EditorLogo />

        {config.mode !== "email" && (
          <div className="relative flex-shrink-0">
            <Select value={slug || ""} onValueChange={(value) => setPage(value)}>
              <SelectTrigger className="h-8 w-[160px] border-none bg-transparent font-medium text-gray-800 hover:bg-gray-100 focus:ring-0 focus:ring-offset-0">
                <SelectValue placeholder="Select page…" />
              </SelectTrigger>
              <SelectContent>
                {pages.map((p) => (
                  <SelectItem key={p.slug} value={p.slug}>
                    {p.title}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
        )}

        <LanguageSelector currentLang={lang} onLangChange={setLang} />
      </div>

      {/* ── Center: Device switcher + Undo/Redo ────────────────── */}
      <div className="flex flex-1 items-center justify-center gap-1">
        <button
          type="button"
          onClick={() => editor.layout.toggleInspector()}
          title={`Inspector mode (${
            inspectorEnabled ? "active" : "inactive"
          }) – Press Cmd+I to toggle`}
          className={cn(
            "flex h-8 w-8 items-center justify-center rounded-lg transition-all duration-200",
            inspectorEnabled
              ? "bg-blue-50 text-blue-600 hover:bg-blue-100 active:bg-blue-200"
              : "text-gray-400 hover:bg-gray-50 active:bg-gray-100",
          )}
        >
          <Crosshair className="h-[18px] w-[18px]" />
        </button>

        {!isMobile && <div className="mx-0.5 h-5 w-px bg-gray-200" />}

        {!isMobile && <DeviceSwitcher device={device} onDeviceChange={setDevice} />}

        <div className="mx-0.5 h-5 w-px bg-gray-200" />

        <UndoRedoControls
          canUndo={canUndo}
          canRedo={canRedo}
          onUndo={() => editor.undo()}
          onRedo={() => editor.redo()}
        />
      </div>

      {/* ── Right: Save button ──────────────────────────── */}
      <div className="flex flex-shrink-0 items-center gap-2">
        <button
          type="button"
          onClick={() => editor.pages.save()}
          disabled={saving || !slug}
          className="rounded-lg bg-gray-900 px-4 py-1.5 text-sm font-semibold text-white transition-all hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-40"
        >
          {saving ? "Saving…" : "Save"}
        </button>
      </div>
    </header>
  )
}
