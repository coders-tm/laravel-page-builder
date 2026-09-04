/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useState } from "react"
import { Globe, Check } from "lucide-react"
import { cn } from "@/lib/utils"
import { Popover, PopoverTrigger, PopoverContent } from "@/components/ui/popover"
import config from "@/config"

interface LanguageSelectorProps {
  /** Currently active language code (null = default) */
  currentLang: string | null
  /** Called with the selected language code when user picks one */
  onLangChange: (lang: string | null) => void
}

/**
 * Compact language selector — icon trigger with a popover dropdown.
 *
 * Hidden when no languages are configured.
 * Shows the default language with a "(Default)" badge.
 */
export default function LanguageSelector({ currentLang, onLangChange }: LanguageSelectorProps) {
  const [open, setOpen] = useState(false)
  const languages = config.languages || []

  if (languages.length === 0) return null

  const defaultLang = languages[0]
  const activeLang = currentLang ? languages.find((l) => l.code === currentLang) || null : null

  const handleSelect = (code: string | null) => {
    onLangChange(code)
    setOpen(false)
  }

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <button
          type="button"
          title="Language"
          className={cn(
            "flex h-8 w-8 items-center justify-center rounded-lg transition-colors duration-150",
            open
              ? "bg-blue-50 text-blue-600"
              : "text-gray-500 hover:bg-gray-100 hover:text-gray-700",
          )}
        >
          <Globe className="h-[18px] w-[18px]" />
        </button>
      </PopoverTrigger>
      <PopoverContent align="start" sideOffset={4} className="w-48 p-1">
        <div className="px-2 py-1.5 text-xs font-medium text-gray-400">Languages</div>
        {languages.map((lang) => {
          const isActive = currentLang === lang.code || (!currentLang && !defaultLang?.code)
          const isDefault = defaultLang?.code === lang.code
          return (
            <button
              key={lang.code}
              type="button"
              onClick={() => handleSelect(lang.code)}
              className={cn(
                "flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors outline-none",
                isActive ? "bg-blue-50 text-blue-600" : "text-gray-700 hover:bg-gray-50",
              )}
            >
              <span className="flex h-4 w-4 items-center justify-center">
                {isActive && <Check className="h-3.5 w-3.5" />}
              </span>
              <span className="flex flex-1 items-center gap-1.5">
                {lang.name}
                <span className="text-xs text-gray-400">{lang.code}</span>
              </span>
              {isDefault && <span className="text-[10px] text-gray-400">Default</span>}
            </button>
          )
        })}
        {defaultLang?.code && (
          <>
            <div className="mx-1 my-1 h-px bg-gray-100" />
            <button
              type="button"
              onClick={() => handleSelect(null)}
              className={cn(
                "flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors outline-none",
                !currentLang ? "bg-blue-50 text-blue-600" : "text-gray-700 hover:bg-gray-50",
              )}
            >
              <span className="flex h-4 w-4 items-center justify-center">
                {!currentLang && <Check className="h-3.5 w-3.5" />}
              </span>
              <span className="flex flex-1 items-center gap-1.5">
                {defaultLang.name}
                <span className="text-xs text-gray-400">{defaultLang.code}</span>
              </span>
              <span className="text-[10px] text-gray-400">Default</span>
            </button>
          </>
        )}
      </PopoverContent>
    </Popover>
  )
}
