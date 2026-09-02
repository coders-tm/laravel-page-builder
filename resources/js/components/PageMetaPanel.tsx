import React, { memo, useCallback } from "react"
import { useStore } from "@/core/store/useStore"
import { useEditorInstance } from "@/core/editorContext"
import type { PageMeta } from "@/types/page-builder"

/**
 * Panel for editing page-level metadata (SEO fields).
 * Reads meta directly from the Zustand store and calls
 * editor.pages.updateMeta() for mutations — no props required.
 */
function PageMetaPanel() {
  const editor = useEditorInstance()
  const { pageMeta: meta } = useStore()

  const handleChange = useCallback(
    (field: keyof PageMeta, value: string) => {
      editor.pages.updateMeta({ [field]: value })
    },
    [editor],
  )

  return (
    <div className="space-y-4 px-4 py-4">
      {/* Page Title */}
      <div>
        <label className="mb-1.5 block text-xs font-semibold text-gray-600">Page title</label>
        <input
          type="text"
          value={meta.title ?? ""}
          onChange={(e) => handleChange("title", e.target.value)}
          placeholder="Page title"
          className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
        />
        <p className="mt-1 text-[11px] text-gray-400">The name used to identify this page.</p>
      </div>

      {/* Meta Title */}
      <div>
        <label className="mb-1.5 block text-xs font-semibold text-gray-600">Meta title</label>
        <input
          type="text"
          value={meta.meta_title ?? ""}
          onChange={(e) => handleChange("meta_title", e.target.value)}
          placeholder="Page title for search engines"
          className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
        />
        <p className="mt-1 text-[11px] text-gray-400">
          Displayed in browser tabs and search engine results.
        </p>
      </div>

      {/* Meta Description */}
      <div>
        <label className="mb-1.5 block text-xs font-semibold text-gray-600">Meta description</label>
        <textarea
          value={meta.meta_description ?? ""}
          onChange={(e) => handleChange("meta_description", e.target.value)}
          placeholder="Brief description for search engines"
          rows={3}
          className="w-full resize-none rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
        />
        <p className="mt-1 text-[11px] text-gray-400">Recommended length: 150–160 characters.</p>
      </div>

      {/* Meta Keywords */}
      <div>
        <label className="mb-1.5 block text-xs font-semibold text-gray-600">Meta keywords</label>
        <input
          type="text"
          value={meta.meta_keywords ?? ""}
          onChange={(e) => handleChange("meta_keywords", e.target.value)}
          placeholder="keyword1, keyword2, keyword3"
          className="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm transition-colors focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 focus:outline-none"
        />
        <p className="mt-1 text-[11px] text-gray-400">Comma-separated keywords for SEO.</p>
      </div>
    </div>
  )
}

export default memo(PageMetaPanel)
