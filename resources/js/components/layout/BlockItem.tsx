/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useState, useMemo, useCallback, useRef, useEffect } from "react"
import { SortableContext, useSortable, verticalListSortingStrategy } from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import { ChevronDown, ChevronRight, Eye, EyeOff, Copy, Trash2, Plus } from "lucide-react"
import { cn } from "@/lib/utils"
import { BlockData, BlockSchema } from "@/types/page-builder"
import { getItemIcon } from "./blockIcons"
import { resolveBlockSchema, canShowAddBlock, getAddableBlockTypes } from "./blockSchema"
import AddBlockRow from "./AddBlockRow"

/* ── BlockItem ────────────────────────────────────────────────────────── */

interface BlockItemProps {
  blockId: string
  block: any
  /** The raw `blocks` array from the parent schema (section or block). Used to resolve this block's schema. */
  parentRawBlocks: Array<{ type: string; name?: string; settings?: any[] }>
  sectionId: string
  siblingOrder: string[]
  parentPath?: string[]
  selectedBlockPath?: string[]
  themeBlocks: Record<string, BlockData>
  onSelectBlock: (sectionId: string, path: string[]) => void
  onRemoveBlock: (sectionId: string, blockId: string, parentPath: string[]) => void
  onDuplicateBlock: (sectionId: string, blockId: string, parentPath: string[]) => void
  onToggleBlockDisabled: (sectionId: string, blockId: string, parentPath: string[]) => void
  onAddBlock: (
    sectionId: string,
    type: string,
    defaults: Record<string, any>,
    afterId?: string | null,
    parentPath?: string[],
  ) => void
  onHover: (sectionId: string | null, blockId: string | null) => void
  onOpenAddBlockModal: (
    types: BlockSchema[],
    sectionId: string,
    parentPath: string[],
    afterBlockId?: string | null,
  ) => void
  onRenameBlock?: (sectionId: string, blockId: string, name: string, parentPath: string[]) => void
  isDraggingGlobal?: boolean
  depth?: number
  /** Incrementing counter — every time it changes, this block (and all its children) collapse. */
  collapseAllSignal?: number
  parentBlocksMap?: Record<string, any> | null
}

export default function BlockItem({
  blockId,
  block,
  parentRawBlocks,
  sectionId,
  siblingOrder,
  parentPath = [],
  selectedBlockPath = [],
  themeBlocks,
  onSelectBlock,
  onRemoveBlock,
  onDuplicateBlock,
  onToggleBlockDisabled,
  onAddBlock,
  onHover,
  onOpenAddBlockModal,
  onRenameBlock,
  isDraggingGlobal = false,
  depth = 0,
  collapseAllSignal = 0,
  parentBlocksMap = null,
}: BlockItemProps) {
  // Resolve this block's own schema using the parent's raw blocks array
  const blockSchema = useMemo(
    () => resolveBlockSchema(block.type, parentRawBlocks, themeBlocks),
    [block.type, parentRawBlocks, themeBlocks],
  )

  // The block's own raw `blocks` stubs — used to resolve children's schemas
  const childRawBlocks = useMemo(() => (blockSchema?.blocks as any[]) || [], [blockSchema?.blocks])

  const canInsertSiblingBlocks = useMemo(() => canShowAddBlock(parentRawBlocks), [parentRawBlocks])

  // Whether the schema declares any child block slots at all (limit-agnostic).
  const schemaSupportsChildren = useMemo(() => canShowAddBlock(childRawBlocks), [childRawBlocks])

  // The list of types the user can choose from when adding a child block.
  // Filtered by per-type limits against the block's current children.
  const addableChildTypes = useMemo(
    () => getAddableBlockTypes(childRawBlocks, themeBlocks, block.blocks),
    [childRawBlocks, themeBlocks, block.blocks],
  )

  // False when every allowed child block type has hit its limit.
  const canAddChildren = addableChildTypes.length > 0

  const addableSiblingBlockTypes = useMemo(
    () => getAddableBlockTypes(parentRawBlocks, themeBlocks, parentBlocksMap),
    [parentRawBlocks, themeBlocks, parentBlocksMap],
  )

  const currentPath = useMemo(
    () => [...parentPath, blockId],
    // parentPath is a new array each render; stringify to stabilise
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [parentPath.join(","), blockId],
  )
  const pathString = currentPath.join(",")
  const selectedPathString = selectedBlockPath.join(",")
  const isSelected = pathString === selectedPathString

  // Resolve name from schema, then theme block schema, then type (capitalized)
  const nameFromSchema = blockSchema?.name
  const nameFromTheme = themeBlocks[block.type]?.schema?.name
  const typeLabel =
    block._name ||
    nameFromSchema ||
    nameFromTheme ||
    block.type.charAt(0).toUpperCase() + block.type.slice(1)

  const previewText =
    block.settings?.heading ||
    block.settings?.title ||
    block.settings?.name ||
    block.settings?.text ||
    null
  const isDisabled = !!block.disabled

  const childOrder = block.order || Object.keys(block.blocks || {})
  const hasChildren = childOrder.length > 0

  // Use parentPath to determine if we should be auto-expanded
  const isParentOfSelected =
    selectedBlockPath.length > currentPath.length &&
    selectedBlockPath.slice(0, currentPath.length).join(",") === pathString

  const [expanded, setExpanded] = useState(isParentOfSelected)
  // Always reflects the latest expanded value so the drag snapshot is accurate.
  const expandedRef = useRef(expanded)
  useEffect(() => {
    expandedRef.current = expanded
  }, [expanded])

  // ── Collapse all (signal from parent — drag-start or "Collapse all" button) ──
  const prevCollapseSignalRef = useRef(collapseAllSignal)
  useEffect(() => {
    if (collapseAllSignal !== prevCollapseSignalRef.current) {
      prevCollapseSignalRef.current = collapseAllSignal
      setExpanded(false)
    }
  }, [collapseAllSignal])

  const BlockIcon = getItemIcon(block.type, hasChildren || canAddChildren, expanded)

  // ── Inline rename state ──────────────────────────────────────────────
  const [renamingBlock, setRenamingBlock] = useState(false)
  const [blockDraft, setBlockDraft] = useState(typeLabel)
  const blockInputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (!renamingBlock) setBlockDraft(typeLabel)
  }, [typeLabel, renamingBlock])

  useEffect(() => {
    if (renamingBlock && blockInputRef.current) {
      blockInputRef.current.focus()
      blockInputRef.current.select()
    }
  }, [renamingBlock])

  const commitBlockRename = useCallback(() => {
    setRenamingBlock(false)
    onRenameBlock?.(sectionId, blockId, blockDraft.trim(), parentPath)
  }, [blockDraft, onRenameBlock, sectionId, blockId, parentPath])

  const cancelBlockRename = useCallback(() => {
    setRenamingBlock(false)
    setBlockDraft(typeLabel)
  }, [typeLabel])

  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: blockId,
    data: {
      type: "block",
      sectionId,
      blockId,
      parentPath,
    },
  })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.4 : 1,
  }

  // ── Collapse THIS block while it is being dragged, restore after ─────
  const expandedBeforeDragRef = useRef<boolean | null>(null)
  useEffect(() => {
    if (isDragging) {
      if (expandedBeforeDragRef.current === null) {
        // Read from the ref so we always get the current value, not a stale closure.
        expandedBeforeDragRef.current = expandedRef.current
      }
      setExpanded(false)
    } else {
      if (expandedBeforeDragRef.current !== null) {
        setExpanded(expandedBeforeDragRef.current)
        expandedBeforeDragRef.current = null
      }
    }
  }, [isDragging])

  React.useEffect(() => {
    if (isParentOfSelected) setExpanded(true)
  }, [isParentOfSelected])

  const handleAddChild = useCallback(() => {
    if (!canAddChildren) return
    const types = addableChildTypes
    if (types.length === 1) {
      const bt = types[0]
      const defaults: Record<string, any> = {}
      ;(bt.settings || []).forEach((s: any) => {
        if (s.default !== undefined) defaults[s.id] = s.default
      })
      onAddBlock(sectionId, bt.type, defaults, null, currentPath)
    } else {
      onOpenAddBlockModal(types, sectionId, currentPath)
    }
    setExpanded(true)
  }, [canAddChildren, addableChildTypes, sectionId, currentPath, onAddBlock, onOpenAddBlockModal])

  // ── Debounced hover ──────────────────────────────────────────────────
  const hoverTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const insertTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)
  const insertIntentRef = useRef<"top" | "bottom" | null>(null)
  const [insertEdge, setInsertEdge] = useState<"top" | "bottom" | null>(null)

  const clearInsertTimer = useCallback(() => {
    if (insertTimerRef.current) {
      clearTimeout(insertTimerRef.current)
      insertTimerRef.current = null
    }
  }, [])

  const getInsertEdge = useCallback((event: React.MouseEvent<HTMLDivElement>): "top" | "bottom" => {
    const rect = event.currentTarget.getBoundingClientRect()
    const y = event.clientY - rect.top
    return y < rect.height / 2 ? "top" : "bottom"
  }, [])

  const handleInsertBlockAtEdge = useCallback(
    (edge: "top" | "bottom") => {
      if (!canInsertSiblingBlocks) return

      const idx = siblingOrder.indexOf(blockId)
      const insertAtStartToken = "__pb_insert_at_start__"
      const afterBlockId =
        edge === "bottom" ? blockId : idx > 0 ? siblingOrder[idx - 1] : insertAtStartToken

      if (addableSiblingBlockTypes.length === 1) {
        const bt = addableSiblingBlockTypes[0]
        const defaults: Record<string, any> = {}
        ;(bt.settings || []).forEach((s: any) => {
          if (s.default !== undefined) defaults[s.id] = s.default
        })
        onAddBlock(sectionId, bt.type, defaults, afterBlockId, parentPath)
      } else {
        onOpenAddBlockModal(addableSiblingBlockTypes, sectionId, parentPath, afterBlockId)
      }
      setInsertEdge(null)
    },
    [
      canInsertSiblingBlocks,
      siblingOrder,
      blockId,
      addableSiblingBlockTypes,
      onAddBlock,
      sectionId,
      parentPath,
      onOpenAddBlockModal,
    ],
  )

  const handleMouseEnter = useCallback(
    (event: React.MouseEvent<HTMLDivElement>) => {
      if (isDraggingGlobal || isDragging) return

      if (canInsertSiblingBlocks) {
        const edge = getInsertEdge(event)
        insertIntentRef.current = edge
        clearInsertTimer()
        insertTimerRef.current = setTimeout(() => {
          if (!insertIntentRef.current) return
          setInsertEdge(insertIntentRef.current)
          insertTimerRef.current = null
        }, 2000)
      }

      if (hoverTimerRef.current) {
        clearTimeout(hoverTimerRef.current)
      }
      hoverTimerRef.current = setTimeout(() => {
        onHover(sectionId, blockId)
      }, 60)
    },
    [
      isDraggingGlobal,
      isDragging,
      canInsertSiblingBlocks,
      getInsertEdge,
      clearInsertTimer,
      onHover,
      sectionId,
      blockId,
    ],
  )

  const handleMouseMove = useCallback(
    (event: React.MouseEvent<HTMLDivElement>) => {
      if (isDraggingGlobal || isDragging || !canInsertSiblingBlocks) return
      const edge = getInsertEdge(event)
      insertIntentRef.current = edge
      setInsertEdge((prev) => (prev ? edge : prev))
    },
    [isDraggingGlobal, isDragging, canInsertSiblingBlocks, getInsertEdge],
  )

  const handleRowMouseLeave = useCallback(() => {
    if (hoverTimerRef.current) {
      clearTimeout(hoverTimerRef.current)
      hoverTimerRef.current = null
    }
    if (!isDraggingGlobal) onHover(null, null)
    clearInsertTimer()
    insertIntentRef.current = null
    setInsertEdge(null)
  }, [isDraggingGlobal, onHover, clearInsertTimer])

  useEffect(() => {
    return () => {
      if (hoverTimerRef.current) clearTimeout(hoverTimerRef.current)
      if (insertTimerRef.current) clearTimeout(insertTimerRef.current)
    }
  }, [])

  useEffect(() => {
    if (isDragging || isDraggingGlobal) {
      clearInsertTimer()
      insertIntentRef.current = null
      setInsertEdge(null)
    }
  }, [isDragging, isDraggingGlobal, clearInsertTimer])

  return (
    <div ref={setNodeRef} style={style} data-block-id={blockId} className="flex flex-col">
      <div
        onClick={() => onSelectBlock(sectionId, currentPath)}
        onMouseEnter={handleMouseEnter}
        onMouseMove={handleMouseMove}
        onMouseLeave={handleRowMouseLeave}
        className={cn(
          "group relative flex cursor-pointer items-center py-[6px] pr-2 transition-colors select-none",
          isDisabled && "opacity-50",
        )}
        style={{ paddingLeft: `${depth * 16 + 8}px` }}
      >
        {canInsertSiblingBlocks && !!insertEdge && (
          <button
            type="button"
            onMouseDown={(e) => e.stopPropagation()}
            onClick={(e) => {
              e.preventDefault()
              e.stopPropagation()
              handleInsertBlockAtEdge(insertEdge)
            }}
            className={`absolute z-20 h-5 ${insertEdge === "top" ? "-top-2.5" : "-bottom-2.5"}`}
            style={{ left: `${depth * 16 + 20}px`, right: "8px" }}
          >
            <span className="absolute inset-x-0 top-1/2 -translate-y-1/2 border-t border-blue-500" />
            <span className="relative mx-auto flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-white shadow-sm">
              <Plus size={12} strokeWidth={2.5} />
            </span>
          </button>
        )}

        {/* Expand chevron (only for containers) */}
        <button
          onClick={(e) => {
            e.stopPropagation()
            if (hasChildren || canAddChildren) setExpanded((v) => !v)
          }}
          className={cn(
            "mr-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded transition-colors",
            hasChildren || canAddChildren
              ? "text-gray-400 hover:text-gray-600"
              : "cursor-default text-transparent",
          )}
          tabIndex={hasChildren || canAddChildren ? 0 : -1}
        >
          {(hasChildren || canAddChildren) &&
            (expanded ? (
              <ChevronDown size={11} strokeWidth={2} />
            ) : (
              <ChevronRight size={11} strokeWidth={2} />
            ))}
        </button>

        {/* Block icon / drag handle */}
        <div
          {...attributes}
          {...listeners}
          className="mr-1.5 flex h-4 w-4 shrink-0 cursor-grab touch-none items-center justify-center active:cursor-grabbing"
          title="Drag to reorder"
        >
          <BlockIcon
            size={13}
            strokeWidth={1.5}
            className={cn(
              isSelected
                ? "text-blue-500"
                : "text-gray-400 transition-colors group-hover:text-gray-600",
            )}
          />
        </div>

        {/* Label — click to rename when onRenameBlock is wired */}
        {renamingBlock ? (
          <input
            ref={blockInputRef}
            value={blockDraft}
            onChange={(e) => setBlockDraft(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                e.preventDefault()
                commitBlockRename()
              } else if (e.key === "Escape") {
                e.preventDefault()
                cancelBlockRename()
              }
            }}
            onBlur={commitBlockRename}
            onClick={(e) => e.stopPropagation()}
            className="min-w-0 flex-1 border-b border-blue-400 bg-transparent text-[11.5px] leading-none font-medium text-gray-800 focus:outline-none"
          />
        ) : (
          <span
            className={cn(
              "flex-1 truncate text-[11.5px] leading-none transition-colors",
              isSelected
                ? "font-semibold text-blue-600"
                : "text-gray-600 group-hover:text-gray-900",
              onRenameBlock && "cursor-default",
            )}
            onDoubleClick={(e) => {
              if (onRenameBlock) {
                e.stopPropagation()
                setBlockDraft(typeLabel)
                setRenamingBlock(true)
              }
            }}
            title={onRenameBlock ? "Double-click to rename" : undefined}
          >
            {typeLabel}
            {previewText && !block._name && (
              <span className="font-normal text-gray-400 italic transition-colors group-hover:text-gray-500">
                {" – "}
                {previewText}
              </span>
            )}
          </span>
        )}

        {/* Action buttons – visible on hover/selected */}
        <div className="ml-1 flex items-center gap-0 opacity-0 transition-opacity group-hover:opacity-100">
          <button
            title={isDisabled ? "Show block" : "Hide block"}
            onClick={(e) => {
              e.stopPropagation()
              onToggleBlockDisabled(sectionId, blockId, parentPath)
            }}
            className={cn(
              "rounded p-1 transition-colors",
              isDisabled
                ? "text-gray-400 !opacity-100 hover:bg-blue-100 hover:text-blue-500"
                : "text-gray-400 hover:bg-gray-200 hover:text-gray-600",
            )}
            style={isDisabled ? { opacity: 1 } : {}}
          >
            {isDisabled ? (
              <EyeOff size={11} strokeWidth={1.5} />
            ) : (
              <Eye size={11} strokeWidth={1.5} />
            )}
          </button>
          <button
            title="Duplicate block"
            onClick={(e) => {
              e.stopPropagation()
              onDuplicateBlock(sectionId, blockId, parentPath)
            }}
            className="rounded p-1 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600"
          >
            <Copy size={11} strokeWidth={1.5} />
          </button>
          <button
            title="Remove block"
            onClick={(e) => {
              e.stopPropagation()
              onRemoveBlock(sectionId, blockId, parentPath)
            }}
            className="rounded p-1 text-gray-400 transition-colors hover:bg-red-100 hover:text-red-500"
          >
            <Trash2 size={11} strokeWidth={1.5} />
          </button>
        </div>
      </div>

      {/* Recursive children tree + "Add block" row */}
      {(hasChildren || canAddChildren) && expanded && (
        <div className="flex flex-col">
          <SortableContext items={childOrder} strategy={verticalListSortingStrategy}>
            {childOrder.map((childId) => {
              const childBlock = block.blocks[childId]
              if (!childBlock) return null

              return (
                <BlockItem
                  key={childId}
                  blockId={childId}
                  block={childBlock}
                  parentRawBlocks={childRawBlocks}
                  sectionId={sectionId}
                  siblingOrder={childOrder}
                  parentPath={currentPath}
                  selectedBlockPath={selectedBlockPath}
                  themeBlocks={themeBlocks}
                  onSelectBlock={onSelectBlock}
                  onRemoveBlock={onRemoveBlock}
                  onDuplicateBlock={onDuplicateBlock}
                  onToggleBlockDisabled={onToggleBlockDisabled}
                  onAddBlock={onAddBlock}
                  onHover={onHover}
                  onOpenAddBlockModal={onOpenAddBlockModal}
                  onRenameBlock={onRenameBlock}
                  isDraggingGlobal={isDraggingGlobal}
                  depth={depth + 1}
                  collapseAllSignal={collapseAllSignal}
                  parentBlocksMap={block.blocks}
                />
              )
            })}
          </SortableContext>

          {/* Inline "Add block" row */}
          {schemaSupportsChildren && (
            <AddBlockRow depth={depth + 1} onAdd={handleAddChild} disabled={!canAddChildren} />
          )}
        </div>
      )}
    </div>
  )
}
