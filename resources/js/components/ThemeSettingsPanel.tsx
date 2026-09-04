/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { memo, useCallback } from "react"
import { useStore } from "@/core/store/useStore"
import { useEditorInstance } from "@/core/editorContext"
import { ResetIcon } from "@/components/header/icons"
import FieldRenderer from "./settings/fields/FieldRenderer"
import type { ThemeSettingsGroup, SettingSchema } from "@/types/page-builder"

/**
 * Panel for editing global theme settings.
 * Reads themeSettings directly from the Zustand store and calls
 * editor.pages methods for mutations — no props required.
 * Theme settings are saved alongside the page via the main Save button.
 */
function ThemeSettingsPanel() {
  const editor = useEditorInstance()
  const { themeSettings } = useStore()
  const { schema, values } = themeSettings

  const handleChange = useCallback(
    (key: string, val: SettingSchema["default"]) => {
      editor.pages.updateThemeSetting(key, val)
    },
    [editor],
  )

  const handleReset = useCallback(
    (key: string) => {
      editor.pages.resetThemeSetting(key)
    },
    [editor],
  )

  const handleResetAll = useCallback(() => {
    editor.pages.resetAllThemeSettings()
  }, [editor])

  if (!schema || schema.length === 0) {
    return (
      <div className="flex flex-1 flex-col gap-3 p-4 select-none">
        <p className="text-sm font-medium text-gray-400">No theme settings configured</p>
        <p className="text-xs leading-relaxed text-gray-300">
          Define a theme settings schema in your{" "}
          <code className="rounded bg-gray-100 px-1 text-[11px]">pagebuilder.php</code> config to
          enable global theme customisation.
        </p>
      </div>
    )
  }

  return (
    <div className="flex flex-1 flex-col overflow-hidden">
      {/* Panel header */}
      <div className="flex items-center justify-between border-b border-gray-100 px-4 py-2">
        <span className="text-xs font-semibold tracking-wider text-gray-500 uppercase">
          Theme Settings
        </span>
        <button
          type="button"
          onClick={handleResetAll}
          title="Reset all settings to defaults"
          className="flex items-center gap-1 text-xs text-gray-400 transition-colors hover:text-gray-600"
        >
          <ResetIcon className="h-3 w-3" />
          Reset all
        </button>
      </div>

      <div className="sidebar-scroll flex-1 overflow-y-auto">
        {schema.map((group: ThemeSettingsGroup, groupIdx: number) => (
          <div key={group.name || `group-${groupIdx}`}>
            {group.name && (
              <div className="px-4 pt-4 pb-1">
                <h3 className="text-xs font-bold tracking-wider text-gray-500 uppercase">
                  {group.name}
                </h3>
              </div>
            )}

            <div className="border-b border-gray-100 px-4 py-3">
              {group.settings.map((setting: SettingSchema, idx: number) => {
                const settingKey = setting.key ?? setting.id
                const currentValue = values?.[settingKey]
                const isModified = currentValue !== undefined && currentValue !== setting.default

                return (
                  <div
                    key={settingKey || `s-${groupIdx}-${idx}`}
                    className="group/setting relative"
                  >
                    <FieldRenderer
                      setting={setting}
                      value={currentValue}
                      onChange={(val) => handleChange(settingKey, val)}
                    />
                    {isModified && (
                      <button
                        type="button"
                        onClick={() => handleReset(settingKey)}
                        title="Reset to default"
                        className="absolute top-0 right-0 p-1 text-gray-300 opacity-0 transition-opacity group-hover/setting:opacity-100 hover:text-gray-500"
                      >
                        <ResetIcon className="h-3 w-3" />
                      </button>
                    )}
                  </div>
                )
              })}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export default memo(ThemeSettingsPanel)
