/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import {
  Layers,
  Type,
  Image,
  Video,
  Rows,
  Columns,
  MousePointer2,
  ExternalLink,
  AlignLeft,
  FolderOpen,
  Folder,
} from "lucide-react"

/* ── Icon Mapping ────────────────────────────────────────────────────── */

/** Returns a Lucide icon component based on block type string. */
export const getBlockIcon = (type: string) => {
  const t = type.toLowerCase()
  if (t.includes("text") || t.includes("heading") || t.includes("paragraph")) return Type
  if (t.includes("image")) return Image
  if (t.includes("video")) return Video
  if (t.includes("row")) return Rows
  if (t.includes("column")) return Columns
  if (t.includes("button") || t.includes("link")) return ExternalLink
  if (t.includes("section")) return Layers
  if (t.includes("@theme")) return MousePointer2
  return AlignLeft
}

/** Returns a folder icon for container-type blocks, otherwise the block icon. */
export const getItemIcon = (type: string, hasChildren: boolean, expanded: boolean) => {
  const t = type.toLowerCase()
  const isContainer =
    hasChildren ||
    t.includes("row") ||
    t.includes("column") ||
    t.includes("group") ||
    t.includes("section")
  if (isContainer) return expanded ? FolderOpen : Folder
  return getBlockIcon(type)
}
