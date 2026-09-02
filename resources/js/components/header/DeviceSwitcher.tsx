import React from "react"
import { Monitor, Smartphone, Maximize2 } from "lucide-react"
import { cn } from "@/lib/utils"

/**
 * Device definitions.
 *
 * Extracted as a constant so both the switcher UI and the
 * PreviewCanvas can reference the same source of truth.
 */
export const DEVICES = [
  { key: "desktop", icon: Monitor, label: "Desktop", width: "100%" },
  { key: "mobile", icon: Smartphone, label: "Mobile", width: "390px" },
  {
    key: "fullscreen",
    icon: Maximize2,
    label: "Full width",
    width: "100%",
  },
] as const

interface DeviceSwitcherProps {
  /** Currently active device key */
  device: string
  /** Called with the selected device key when user clicks a button */
  onDeviceChange: (device: string) => void
}

/**
 * Segmented device-switcher control.
 *
 * SRP: solely responsible for rendering the device toggle buttons
 * and forwarding the selection upward.
 */
export default function DeviceSwitcher({ device, onDeviceChange }: DeviceSwitcherProps) {
  return (
    <div className="flex items-center gap-0.5 rounded-lg bg-transparent p-0.5">
      {DEVICES.map((d) => {
        const isActive = device === d.key
        return (
          <button
            key={d.key}
            type="button"
            title={d.label}
            onClick={() => onDeviceChange(d.key)}
            className={cn(
              "flex h-8 w-8 items-center justify-center rounded-lg transition-colors duration-150",
              isActive
                ? "bg-white text-gray-900 shadow-sm"
                : "text-gray-500 hover:bg-gray-100 hover:text-gray-700",
            )}
          >
            <d.icon className="h-[18px] w-[18px]" />
          </button>
        )
      })}
    </div>
  )
}
