import React, { useCallback, useSyncExternalStore } from "react";
import { DeviceSwitcher, UndoRedoControls, EditorLogo } from "./header";
import { Crosshair, LogOut } from "lucide-react";
import { cn } from "@/lib/utils";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "./ui/select";
import { useEditorInstance } from "@/core/editorContext";
import { useEditorNavigation } from "@/hooks/useEditorNavigation";
import { useEditorLayout } from "@/hooks/useEditorLayout";
import { useStore } from "@/core/store/useStore";
import { useMaxBreakpoint } from "@/hooks/useBreakpoint";
import config from "@/config";

/**
 * Top header bar — reads all state directly from the editor context,
 * store, and manager hooks. No props required.
 */
export default function EditorHeader() {
  const editor = useEditorInstance();
  const { pages, saving } = useStore();
  const { slug, device, setPage, setDevice } = useEditorNavigation();
  const { inspectorEnabled } = useEditorLayout();
  const isMobile = useMaxBreakpoint(768);

  // Subscribe to history manager for reactive canUndo / canRedo.
  useSyncExternalStore(
    useCallback((listener) => editor.history.subscribe(listener), [editor]),
    useCallback(() => editor.history.getVersion(), [editor]),
    () => 0,
  );
  const canUndo = editor.history.canUndo;
  const canRedo = editor.history.canRedo;

  return (
    <header className="flex items-center h-[52px] px-3 bg-white border-b border-gray-200 flex-shrink-0 gap-1 z-50">
      {/* ── Left: Logo + Page selector ─────────────────────────── */}
      <div className="flex items-center gap-1.5 flex-shrink-0">
        <button
          type="button"
          onClick={() => {
            window.dispatchEvent(new CustomEvent("pagebuilder:exit"));
          }}
          className="p-1.5 rotate-180 rounded-lg text-gray-400 hover:bg-gray-100 transition-colors"
          title="Exit Editor"
        >
          <LogOut className="w-[18px] h-[18px]" />
        </button>

        <EditorLogo />

        {config.mode !== "email" && (
          <div className="relative flex-shrink-0">
            <Select
              value={slug || ""}
              onValueChange={(value) => setPage(value)}
            >
              <SelectTrigger className="h-8 w-[160px] bg-transparent border-none hover:bg-gray-100 focus:ring-0 focus:ring-offset-0 font-medium text-gray-800">
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
      </div>

      {/* ── Center: Device switcher + Undo/Redo ────────────────── */}
      <div className="flex items-center justify-center flex-1 gap-1">
        <button
          type="button"
          onClick={() => editor.layout.toggleInspector()}
          title={`Inspector mode (${
            inspectorEnabled ? "active" : "inactive"
          }) – Press Cmd+I to toggle`}
          className={cn(
            "flex items-center justify-center w-8 h-8 rounded-lg transition-all duration-200",
            inspectorEnabled
              ? "text-blue-600 bg-blue-50 hover:bg-blue-100 active:bg-blue-200"
              : "text-gray-400 hover:bg-gray-50 active:bg-gray-100",
          )}
        >
          <Crosshair className="w-[18px] h-[18px]" />
        </button>

        {!isMobile && <div className="w-px h-5 bg-gray-200 mx-0.5" />}

        {!isMobile && (
          <DeviceSwitcher device={device} onDeviceChange={setDevice} />
        )}

        <div className="w-px h-5 bg-gray-200 mx-0.5" />

        <UndoRedoControls
          canUndo={canUndo}
          canRedo={canRedo}
          onUndo={() => editor.undo()}
          onRedo={() => editor.redo()}
        />
      </div>

      {/* ── Right: Save button ──────────────────────────── */}
      <div className="flex items-center gap-2 flex-shrink-0">
        <button
          type="button"
          onClick={() => editor.pages.save()}
          disabled={saving || !slug}
          className="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all
                        bg-gray-900 text-white hover:bg-gray-800
                        disabled:opacity-40 disabled:cursor-not-allowed"
        >
          {saving ? "Saving…" : "Save"}
        </button>
      </div>
    </header>
  );
}
