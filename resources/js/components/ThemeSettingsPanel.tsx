import React, { memo, useCallback } from "react";
import { useStore } from "@/core/store/useStore";
import { useEditorInstance } from "@/core/editorContext";
import FieldRenderer from "./settings/fields/FieldRenderer";

/**
 * Panel for editing global theme settings.
 * Reads themeSettings directly from the Zustand store and calls
 * editor.pages methods for mutations — no props required.
 * Theme settings are saved alongside the page via the main Save button.
 */
function ThemeSettingsPanel() {
    const editor = useEditorInstance();
    const { themeSettings } = useStore();
    const { schema, values } = themeSettings;

    const handleChange = useCallback(
        (key: string, val: any) => {
            editor.pages.updateThemeSetting(key, val);
        },
        [editor]
    );

    const handleReset = useCallback(
        (key: string) => {
            editor.pages.resetThemeSetting(key);
        },
        [editor]
    );

    const handleResetAll = useCallback(() => {
        editor.pages.resetAllThemeSettings();
    }, [editor]);

    if (!schema || schema.length === 0) {
        return (
            <div className="flex flex-col flex-1 p-4 gap-3 select-none">
                <p className="text-sm font-medium text-gray-400">
                    No theme settings configured
                </p>
                <p className="text-xs text-gray-300 leading-relaxed">
                    Define a theme settings schema in your{" "}
                    <code className="bg-gray-100 px-1 rounded text-[11px]">
                        pagebuilder.php
                    </code>{" "}
                    config to enable global theme customisation.
                </p>
            </div>
        );
    }

    return (
        <div className="flex flex-col flex-1 overflow-hidden">
            {/* Panel header */}
            <div className="flex items-center justify-end px-4 py-2 border-b border-gray-100">
                <button
                    type="button"
                    onClick={handleResetAll}
                    title="Reset all settings to defaults"
                    className="flex items-center gap-1 text-xs text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <ResetIcon className="w-3 h-3" />
                    Reset all
                </button>
            </div>

            <div className="flex-1 overflow-y-auto sidebar-scroll">
                {schema.map((group: any, groupIdx: number) => (
                    <div key={group.name || `group-${groupIdx}`}>
                        {group.name && (
                            <div className="px-4 pt-4 pb-1">
                                <h3 className="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    {group.name}
                                </h3>
                            </div>
                        )}

                        <div className="px-4 py-3 border-b border-gray-100">
                            {group.settings.map((setting: any, idx: number) => {
                                const settingKey = setting.key ?? setting.id;
                                const currentValue = values?.[settingKey];
                                const isModified =
                                    currentValue !== undefined &&
                                    currentValue !== setting.default;

                                return (
                                    <div
                                        key={settingKey || `s-${groupIdx}-${idx}`}
                                        className="relative group/setting"
                                    >
                                        <FieldRenderer
                                            setting={setting}
                                            value={currentValue}
                                            onChange={(val) =>
                                                handleChange(settingKey, val)
                                            }
                                        />
                                        {isModified && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    handleReset(settingKey)
                                                }
                                                title="Reset to default"
                                                className="absolute top-0 right-0 p-1 text-gray-300 hover:text-gray-500 opacity-0 group-hover/setting:opacity-100 transition-opacity"
                                            >
                                                <ResetIcon className="w-3 h-3" />
                                            </button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}

function ResetIcon({ className }: { className?: string }) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
        >
            <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" />
            <path d="M3 3v5h5" />
        </svg>
    );
}

export default memo(ThemeSettingsPanel);
