/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { BlockData, BlockInstance, BlockSchema } from "@/types/page-builder"

/* ── Schema Resolution Utilities ─────────────────────────────────────── */

/**
 * An entry in a `blocks` array is a LOCAL block definition when it carries
 * more than just `type` — i.e. it has `name`, `settings`, or any other keys.
 * A theme-block reference is bare: only `{ type }` (or `{ type: '@theme' }`).
 *
 * Examples
 *  { type: 'column' }                          → theme-block reference (bare)
 *  { type: '@theme' }                          → @theme wildcard reference (bare)
 *  { type: 'column', name: 'Column', settings: [...] } → local block definition
 */
export function isLocalBlockEntry(entry: { type: string; [key: string]: any }): boolean {
  return Object.keys(entry).some((k) => k !== "type")
}

/**
 * Returns true if the raw blocks array is in "@theme" mode:
 * exactly one entry `{ type: '@theme' }`.
 */
export function isThemeMode(
  raw: Array<{ type: string; [key: string]: any }> | undefined | null,
): boolean {
  return Array.isArray(raw) && raw.length === 1 && raw[0].type === "@theme"
}

/**
 * Resolve the full schema for a child block instance using the parent's raw
 * `blocks` array.
 *
 * Detection — an entry is local when it has keys beyond `type` (name, settings…).
 * A bare `{ type: 'foo' }` is a theme-block reference.
 *
 * Resolution rules:
 *
 *  Parent blocks entry   │ Resolution
 *  ──────────────────────┼──────────────────────────────────────────────────
 *  { type: '@theme' }    │ Full schema from themeBlocks (name, settings,
 *                        │ blocks child-slot) — any theme block is allowed.
 *  { type: 'foo' }       │ Bare theme-block reference → full schema from
 *  (bare, ref only)      │ themeBlocks['foo'], incl. its own child-slot.
 *  { type, name,         │ Local block definition → use local entry as-is.
 *    settings, … }       │ Its own `blocks` key (if present) governs the
 *  (has extra keys)      │ child-slot. No theme-registry lookup for blocks.
 *  ──────────────────────┼──────────────────────────────────────────────────
 *  No match found        │ Fallback to themeBlocks.
 */
export function resolveBlockSchema(
  blockType: string,
  parentRawBlocks: Array<{ type: string; [key: string]: any }> | undefined | null,
  themeBlocks: Record<string, BlockData>,
): BlockSchema | null {
  const raw = parentRawBlocks || []

  // ── @theme wildcard ────────────────────────────────────────────────
  if (isThemeMode(raw)) {
    return themeBlocks[blockType]?.schema ?? null
  }

  const entry = raw.find((b) => b.type === blockType)

  if (entry) {
    if (isLocalBlockEntry(entry)) {
      // Local definition — authoritative as-is (no theme merge for child-slot).
      return entry as unknown as BlockSchema
    } else {
      // Bare theme-block reference → resolve full schema from theme registry.
      return themeBlocks[blockType]?.schema ?? null
    }
  }

  // Fallback: block added when schema mode differed, or unknown type.
  return themeBlocks[blockType]?.schema ?? null
}

/**
 * Returns all addable BlockSchema objects for a given parent's raw blocks array,
 * optionally filtered by per-type limits.
 *
 *  @theme mode → every registered theme block.
 *  local entry → use the local definition as-is.
 *  bare ref    → resolve full schema from theme registry.
 *  empty       → [] (no "Add block").
 *
 * When `currentBlocks` is supplied, any block type whose `limit > 0` and whose
 * current count in `currentBlocks` has reached that limit is marked with
 * `disabled: true` — it remains visible in the picker but cannot be added.
 */
export function getAddableBlockTypes(
  parentRawBlocks: Array<{ type: string; [key: string]: any }> | undefined | null,
  themeBlocks: Record<string, BlockData>,
  currentBlocks?: Record<string, BlockInstance> | null,
): BlockSchema[] {
  const raw = parentRawBlocks || []

  // Count existing blocks per type so we can enforce per-type limits.
  const countByType: Record<string, number> = {}
  if (currentBlocks) {
    for (const block of Object.values(currentBlocks)) {
      countByType[block.type] = (countByType[block.type] ?? 0) + 1
    }
  }

  const markIfAtLimit = (schema: BlockSchema): BlockSchema => {
    const limit = schema.limit ?? 0
    if (limit <= 0) return schema
    const atLimit = (countByType[schema.type] ?? 0) >= limit
    return atLimit ? { ...schema, disabled: true } : schema
  }

  if (isThemeMode(raw)) {
    return Object.values(themeBlocks).map((bd) => markIfAtLimit(bd.schema))
  }

  if (raw.length > 0) {
    return raw
      .filter((b) => b.type !== "@theme") // safety: skip stray @theme entries
      .map((entry) => {
        if (isLocalBlockEntry(entry)) {
          // Local definition — used as-is for the picker; mark as local
          // so callers (e.g. AddBlockModal) can skip the preview API call.
          return markIfAtLimit({ ...(entry as unknown as BlockSchema), local: true })
        }
        // Bare theme-block reference — resolve from registry.
        const schema = themeBlocks[entry.type]?.schema ?? (entry as unknown as BlockSchema)
        return markIfAtLimit(schema)
      })
  }

  return []
}

/**
 * Returns true if an "Add block" button should be shown.
 *
 *  @theme mode → true (any theme block can be added)
 *  non-empty   → true (local definitions or bare theme refs are addable)
 *  empty       → false
 */
export function canShowAddBlock(
  parentRawBlocks: Array<{ type: string; [key: string]: any }> | undefined | null,
): boolean {
  const raw = parentRawBlocks || []
  if (raw.length === 0) return false
  // True for @theme mode, bare theme refs, and local definitions alike.
  return true
}
