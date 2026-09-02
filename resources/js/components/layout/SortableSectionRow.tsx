import React, { useState, useMemo, useCallback, useRef, useEffect } from "react"
import { SortableContext, useSortable, verticalListSortingStrategy } from "@dnd-kit/sortable"
import { CSS } from "@dnd-kit/utilities"
import {
  ChevronDown,
  ChevronRight,
  Eye,
  EyeOff,
  Copy,
  Trash2,
  FolderOpen,
  Folder,
  Plus,
} from "lucide-react"
import { cn } from "@/lib/utils"
import { BlockData, BlockSchema, SectionData } from "@/types/page-builder"
import { canShowAddBlock, getAddableBlockTypes } from "./blockSchema"
import AddBlockRow from "./AddBlockRow"
import BlockItem from "./BlockItem"

/* ── SortableSectionRow ───────────────────────────────────────────────── */

interface SortableSectionRowProps {
  sectionId: string
  section: any
  meta: SectionData | undefined
  themeBlocks: Record<string, BlockData>
  isSelected: boolean
  selectedBlockPath: string[]
  onSelect: (sectionId: string) => void
  onSelectBlock: (sectionId: string, path: string[]) => void
  onRemove: (sectionId: string) => void
  onDuplicate: (sectionId: string) => void
  onToggleSectionDisabled: (sectionId: string) => void
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
  onOpenAddSectionAtEdge?: (sectionId: string, edge: "top" | "bottom") => void
  onRenameSection?: (sectionId: string, name: string) => void
  onRenameBlock?: (sectionId: string, blockId: string, name: string, parentPath: string[]) => void
  isDraggingGlobal?: boolean
  /** Incrementing counter — every time it changes, this section (and all its blocks) collapse. */
  collapseAllSignal?: number
}

export default function SortableSectionRow({
  sectionId,
  section,
  meta,
  themeBlocks,
  isSelected,
  selectedBlockPath,
  onSelect,
  onSelectBlock,
  onRemove,
  onDuplicate,
  onToggleSectionDisabled,
  onRemoveBlock,
  onDuplicateBlock,
  onToggleBlockDisabled,
  onAddBlock,
  onHover,
  onOpenAddBlockModal,
  onOpenAddSectionAtEdge,
  onRenameSection,
  onRenameBlock,
  isDraggingGlobal = false,
  collapseAllSignal = 0,
}: SortableSectionRowProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: sectionId,
  })

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.4 : 1,
  }

  const name = section._name || meta?.schema?.name || section.type
  const isDisabled = !!section.disabled

  // ── Inline rename state ──────────────────────────────────────────────
  const [renamingSection, setRenamingSection] = useState(false)
  const [sectionDraft, setSectionDraft] = useState(name)
  const sectionInputRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (!renamingSection) setSectionDraft(name)
  }, [name, renamingSection])

  useEffect(() => {
    if (renamingSection && sectionInputRef.current) {
      sectionInputRef.current.focus()
      sectionInputRef.current.select()
    }
  }, [renamingSection])

  const commitSectionRename = useCallback(() => {
    setRenamingSection(false)
    onRenameSection?.(sectionId, sectionDraft.trim())
  }, [sectionDraft, onRenameSection, sectionId])

  const cancelSectionRename = useCallback(() => {
    setRenamingSection(false)
    setSectionDraft(name)
  }, [name])

  // Resolve the section schema's raw blocks array.
  const sectionSchema = meta?.schema
  const sectionRawBlocks = useMemo(
    () => (sectionSchema?.blocks as any[]) || [],
    [sectionSchema?.blocks],
  )

  // Whether the schema declares any block slots at all (schema-level, limit-agnostic).
  const schemaSupportsBlocks = useMemo(() => canShowAddBlock(sectionRawBlocks), [sectionRawBlocks])

  // The list of types still under their per-type limit.
  const addableBlockTypes = useMemo(
    () => getAddableBlockTypes(sectionRawBlocks, themeBlocks, section.blocks),
    [sectionRawBlocks, themeBlocks, section.blocks],
  )

  // False when every allowed block type has hit its limit.
  const canAddBlocks = addableBlockTypes.length > 0

  const blockOrder: string[] = section.order || Object.keys(section.blocks || {})
  const hasBlocks = blockOrder.length > 0

  const [expanded, setExpanded] = useState(true)
  // Always reflects the latest expanded value so the drag snapshot is accurate.
  const expandedRef = useRef(expanded)
  useEffect(() => {
    expandedRef.current = expanded
  }, [expanded])

  // ── Collapse all (signal from parent — drag-start or "Collapse all" button) ──
  // collapseAllSignal starts at 0; every increment triggers a full collapse.
  const prevCollapseSignalRef = useRef(collapseAllSignal)
  useEffect(() => {
    if (collapseAllSignal !== prevCollapseSignalRef.current) {
      prevCollapseSignalRef.current = collapseAllSignal
      setExpanded(false)
    }
  }, [collapseAllSignal])

  // ── Collapse THIS section while it is being dragged, restore after ───
  const expandedBeforeDragRef = useRef<boolean | null>(null)
  useEffect(() => {
    // Collapse when THIS item is dragged OR when any drag is in progress globally
    const shouldCollapse = isDragging || isDraggingGlobal
    if (shouldCollapse) {
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
  }, [isDragging, isDraggingGlobal])

  const SectionIcon = expanded ? FolderOpen : Folder

  // Auto-expand when a block inside this section is selected (e.g. from canvas click)
  React.useEffect(() => {
    if (isSelected && selectedBlockPath.length > 0) {
      setExpanded(true)
    }
  }, [isSelected, selectedBlockPath])

  const handleAddBlock = useCallback(() => {
    if (!canAddBlocks) return
    const types = addableBlockTypes
    if (types.length === 1) {
      const bt = types[0]
      const defaults: Record<string, any> = {}
      ;(bt.settings || []).forEach((s: any) => {
        if (s.default !== undefined) defaults[s.id] = s.default
      })
      onAddBlock(sectionId, bt.type, defaults, null, [])
    } else {
      onOpenAddBlockModal(types, sectionId, [])
    }
    setExpanded(true)
  }, [canAddBlocks, addableBlockTypes, sectionId, onAddBlock, onOpenAddBlockModal])

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

  const handleMouseEnter = useCallback(
    (event: React.MouseEvent<HTMLDivElement>) => {
      if (isDraggingGlobal || isDragging) return
      const edge = getInsertEdge(event)
      insertIntentRef.current = edge
      clearInsertTimer()
      insertTimerRef.current = setTimeout(() => {
        if (!insertIntentRef.current) return
        setInsertEdge(insertIntentRef.current)
        insertTimerRef.current = null
      }, 2000)

      if (hoverTimerRef.current) {
        clearTimeout(hoverTimerRef.current)
      }
      hoverTimerRef.current = setTimeout(() => {
        onHover(sectionId, null)
      }, 60)
    },
    [isDraggingGlobal, isDragging, getInsertEdge, clearInsertTimer, onHover, sectionId],
  )

  const handleMouseMove = useCallback(
    (event: React.MouseEvent<HTMLDivElement>) => {
      if (isDraggingGlobal || isDragging) return
      const edge = getInsertEdge(event)
      insertIntentRef.current = edge
      setInsertEdge((prev) => (prev ? edge : prev))
    },
    [isDraggingGlobal, isDragging, getInsertEdge],
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

  const handleInsertAtEdge = useCallback(
    (edge: "top" | "bottom") => {
      if (!onOpenAddSectionAtEdge) return
      onOpenAddSectionAtEdge(sectionId, edge)
      setInsertEdge(null)
    },
    [onOpenAddSectionAtEdge, sectionId],
  )

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
    <div ref={setNodeRef} style={style} data-section-id={sectionId}>
      {/* Section header row */}
      <div
        className={cn(
          "group relative flex cursor-pointer items-center px-2 py-[8px] transition-colors select-none",
          isDisabled && "opacity-50",
        )}
        onClick={() => onSelect(sectionId)}
        onMouseEnter={handleMouseEnter}
        onMouseMove={handleMouseMove}
        onMouseLeave={handleRowMouseLeave}
      >
        {!!onOpenAddSectionAtEdge && !!insertEdge && (
          <button
            type="button"
            onMouseDown={(e) => e.stopPropagation()}
            onClick={(e) => {
              e.preventDefault()
              e.stopPropagation()
              handleInsertAtEdge(insertEdge)
            }}
            className={`absolute right-3 left-3 z-20 h-5 ${
              insertEdge === "top" ? "-top-2.5" : "-bottom-2.5"
            }`}
          >
            <span className="absolute inset-x-0 top-1/2 -translate-y-1/2 border-t border-blue-500" />
            <span className="relative mx-auto flex h-5 w-5 items-center justify-center rounded-full bg-blue-500 text-white shadow-sm">
              <Plus size={12} strokeWidth={2.5} />
            </span>
          </button>
        )}

        {/* Expand chevron */}
        <button
          onClick={(e) => {
            e.stopPropagation()
            if (hasBlocks || canAddBlocks) setExpanded((v) => !v)
          }}
          className={cn(
            "mr-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded transition-colors",
            hasBlocks || canAddBlocks
              ? "text-gray-400 hover:text-gray-600"
              : "cursor-default text-transparent",
          )}
          tabIndex={hasBlocks || canAddBlocks ? 0 : -1}
        >
          {(hasBlocks || canAddBlocks) &&
            (expanded ? (
              <ChevronDown size={12} strokeWidth={2} />
            ) : (
              <ChevronRight size={12} strokeWidth={2} />
            ))}
        </button>

        {/* Folder icon / drag handle */}
        <div
          {...attributes}
          {...listeners}
          className="mr-2 flex h-4 w-4 shrink-0 cursor-pointer touch-none items-center justify-center active:cursor-grabbing"
          title="Drag to reorder"
        >
          <SectionIcon
            size={14}
            strokeWidth={1.5}
            className={cn(
              isSelected && selectedBlockPath.length === 0
                ? "text-blue-500"
                : "text-gray-400 transition-colors group-hover:text-gray-600",
            )}
          />
        </div>

        {/* Section name — click to rename */}
        {renamingSection ? (
          <input
            ref={sectionInputRef}
            value={sectionDraft}
            onChange={(e) => setSectionDraft(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter") {
                e.preventDefault()
                commitSectionRename()
              } else if (e.key === "Escape") {
                e.preventDefault()
                cancelSectionRename()
              }
            }}
            onBlur={commitSectionRename}
            onClick={(e) => e.stopPropagation()}
            className="min-w-0 flex-1 border-b border-blue-400 bg-transparent text-xs font-semibold text-gray-800 focus:outline-none"
          />
        ) : (
          <span
            className={cn(
              "flex-1 truncate text-xs font-semibold transition-colors",
              isSelected && selectedBlockPath.length === 0
                ? "text-blue-600"
                : "text-gray-700 group-hover:text-gray-900",
              onRenameSection && "cursor-default",
            )}
            onDoubleClick={(e) => {
              if (onRenameSection) {
                e.stopPropagation()
                setSectionDraft(name)
                setRenamingSection(true)
              }
            }}
            title={onRenameSection ? "Double-click to rename" : undefined}
          >
            {name}
          </span>
        )}

        {/* Action buttons – visible on hover */}
        <div className="flex items-center gap-0 opacity-0 transition-opacity group-hover:opacity-100">
          <button
            title={isDisabled ? "Show section" : "Hide section"}
            onClick={(e) => {
              e.stopPropagation()
              onToggleSectionDisabled(sectionId)
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
              <EyeOff size={13} strokeWidth={1.5} />
            ) : (
              <Eye size={13} strokeWidth={1.5} />
            )}
          </button>
          <button
            title="Duplicate section"
            onClick={(e) => {
              e.stopPropagation()
              onDuplicate(sectionId)
            }}
            className="rounded p-1 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-600"
          >
            <Copy size={12} strokeWidth={1.5} />
          </button>
          <button
            title="Remove section"
            onClick={(e) => {
              e.stopPropagation()
              onRemove(sectionId)
            }}
            className="rounded p-1 text-gray-400 transition-colors hover:bg-red-100 hover:text-red-500"
          >
            <Trash2 size={12} strokeWidth={1.5} />
          </button>
        </div>
      </div>

      {/* Expandable block tree */}
      {(hasBlocks || canAddBlocks) && expanded && (
        <div className="bg-white pb-0.5">
          <SortableContext items={blockOrder} strategy={verticalListSortingStrategy}>
            {blockOrder.map((blockId) => {
              const block = section.blocks?.[blockId]
              if (!block) return null
              return (
                <BlockItem
                  key={blockId}
                  blockId={blockId}
                  block={block}
                  parentRawBlocks={sectionRawBlocks}
                  sectionId={sectionId}
                  siblingOrder={blockOrder}
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
                  collapseAllSignal={collapseAllSignal}
                  depth={1}
                  parentBlocksMap={section.blocks}
                />
              )
            })}
          </SortableContext>

          {/* Inline "Add block" row at section level — disabled when all types hit their limit */}
          {schemaSupportsBlocks && (
            <AddBlockRow depth={1} onAdd={handleAddBlock} disabled={!canAddBlocks} />
          )}
        </div>
      )}
    </div>
  )
}
