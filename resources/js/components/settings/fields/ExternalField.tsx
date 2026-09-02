import React, { memo, useState, useEffect } from "react"
import { SettingSchema } from "@/types/page-builder"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Skeleton } from "@/components/ui/skeleton"

export interface ExternalListItem {
  label: string | number
  value: string | number
}

interface ExternalFieldProps {
  setting: SettingSchema
  value: string
  onChange: (val: string) => void
  fetchList: () => Promise<ExternalListItem[]>
}

function ExternalField({ setting, value, onChange, fetchList }: ExternalFieldProps) {
  const [options, setOptions] = useState<ExternalListItem[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let isMounted = true
    setLoading(true)
    setError(null)

    fetchList()
      .then((data) => {
        if (isMounted) {
          setOptions(data || [])
          setLoading(false)
        }
      })
      .catch((err) => {
        if (isMounted) {
          console.error(`Error fetching list for ${setting.id}:`, err)
          setError(err.message || "Failed to load options")
          setLoading(false)
        }
      })

    return () => {
      isMounted = false
    }
  }, [fetchList, setting.id])

  if (loading) {
    return <Skeleton className="h-9 w-full bg-slate-100" />
  }

  if (error) {
    return (
      <div className="rounded border border-red-100 bg-red-50 p-2 text-[10px] text-red-500">
        {error}
      </div>
    )
  }

  if (options.length === 0) {
    return <div className="px-2 text-[10px] text-gray-500 italic">No options available</div>
  }

  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger className="w-full">
        <SelectValue placeholder={setting.placeholder || "Select an option"} />
      </SelectTrigger>
      <SelectContent>
        {options.map((opt) => (
          <SelectItem key={opt.value} value={String(opt.value)}>
            {opt.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}

export default memo(ExternalField)
