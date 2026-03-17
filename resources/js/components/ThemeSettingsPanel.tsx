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
                            return (
                            <FieldRenderer
                                key={settingKey || `s-${groupIdx}-${idx}`}
                                setting={setting}
                                value={values?.[settingKey]}
                                onChange={(val) =>
                                    handleChange(settingKey, val)
                                }
                            />
                            );
                        })}
                    </div>
                </div>
            ))}
        </div>
    );
}

export default memo(ThemeSettingsPanel);
