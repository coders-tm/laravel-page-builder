import React, { memo, useMemo } from "react";
import { SettingSchema } from "@/types/page-builder";
import { inputCls } from "./TextField";
import { cn } from "@/lib/utils";
import { hexToHsl, hslToHex } from "@/lib/colors";

interface ColorFieldProps {
    setting: SettingSchema;
    value: string;
    onChange: (val: string) => void;
    /** If true, renders as a background (accepts gradients) */
    isBackground?: boolean;
}

function ColorField({
    setting,
    value,
    onChange,
    isBackground = false,
}: ColorFieldProps) {
    const isHsl = setting.mode === "hsl";

    // For the <input type="color"> picker, we always need a hex value.
    const hexValue = useMemo(() => {
        if (!value) return "#000000";
        if (isHsl) {
            try {
                return hslToHex(value);
            } catch (e) {
                return "#000000";
            }
        }
        return /^#[0-9a-fA-F]{3,8}$/.test(value) ? value : "#000000";
    }, [value, isHsl]);

    const handlePickerChange = (hex: string) => {
        if (isHsl) {
            onChange(hexToHsl(hex));
        } else {
            onChange(hex);
        }
    };

    if (isBackground) {
        return (
            <div className="flex items-center gap-2">
                <div
                    className="w-9 h-9 rounded-lg border border-gray-200 shrink-0 shadow-sm transition-all hover:scale-105"
                    style={{ background: value || "#000" }}
                />
                <input
                    type="text"
                    className={cn(
                        inputCls,
                        "flex-1 font-mono text-[11px] h-9 px-3"
                    )}
                    value={value}
                    placeholder="linear-gradient(…) or #hex"
                    onChange={(e) => onChange(e.target.value)}
                />
            </div>
        );
    }

    return (
        <div className="flex items-center gap-2 group">
            <div className="relative w-9 h-9 shrink-0">
                <input
                    type="color"
                    className="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                    value={hexValue}
                    onChange={(e) => handlePickerChange(e.target.value)}
                />
                <div
                    className="w-full h-full rounded-lg border border-gray-200 p-1 shadow-sm group-hover:border-primary-400 transition-colors"
                    style={{ backgroundColor: hexValue }}
                >
                    <div className="w-full h-full rounded-[4px] border border-black/5" />
                </div>
            </div>
            <input
                type="text"
                className={cn(
                    inputCls,
                    "flex-1 font-mono text-[11px] h-9 px-3 bg-gray-50/50 focus:bg-white transition-colors"
                )}
                value={value}
                placeholder={isHsl ? "H S% L%" : "#hex"}
                onChange={(e) => onChange(e.target.value)}
            />
        </div>
    );
}

export default memo(ColorField);
