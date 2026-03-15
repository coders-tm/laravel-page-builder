import React, {
    useState,
    useMemo,
    useCallback,
    useRef,
    useEffect,
} from "react";
import {
    SortableContext,
    verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import {
    ChevronDown,
    ChevronRight,
    Eye,
    EyeOff,
    PanelTop,
    PanelBottom,
} from "lucide-react";
import { cn } from "@/lib/utils";
import {
    BlockData,
    BlockSchema,
    SectionData,
    SectionInstance,
} from "@/types/page-builder";
import { canShowAddBlock, getAddableBlockTypes } from "./blockSchema";
import AddBlockRow from "./AddBlockRow";
import BlockItem from "./BlockItem";

/* ── LayoutSectionRow ─────────────────────────────────────────────────
 *
 * A non-sortable, fixed row representing a layout section slot
 * (e.g. "header" above @yield, "footer" below @yield).
 *
 * Unlike SortableSectionRow, this row has no drag handle, no duplicate
 * action, and no remove action — layout slots are structural and their
 * position is determined by @sections() calls in the Blade layout.
 * ─────────────────────────────────────────────────────────────────── */

interface LayoutSectionRowProps {
    /** Position key used by @sections() directive (e.g. "header", "footer"). */
    sectionKey: string;
    /** The stored per-page override for this layout slot. */
    section: SectionInstance;
    /** Schema/meta from the SectionRegistry for this section type. */
    meta: SectionData | undefined;
    /** Which position this slot occupies in the layout. */
    position: "top" | "bottom";
    /** Whether this row is currently selected (highlighted in settings panel). */
    isSelected: boolean;
    selectedBlockPath: string[];
    themeBlocks: Record<string, BlockData>;
    onSelect: (sectionKey: string) => void;
    onSelectBlock: (sectionKey: string, path: string[]) => void;
    onToggleDisabled: (sectionKey: string) => void;
    onRemoveBlock: (
        sectionKey: string,
        blockId: string,
        parentPath: string[]
    ) => void;
    onDuplicateBlock: (
        sectionKey: string,
        blockId: string,
        parentPath: string[]
    ) => void;
    onToggleBlockDisabled: (
        sectionKey: string,
        blockId: string,
        parentPath: string[]
    ) => void;
    onAddBlock: (
        sectionKey: string,
        type: string,
        defaults: Record<string, any>,
        afterId?: string | null,
        parentPath?: string[]
    ) => void;
    onOpenAddBlockModal: (
        types: BlockSchema[],
        sectionKey: string,
        parentPath: string[],
        afterBlockId?: string | null
    ) => void;
    onRenameBlock?: (
        sectionKey: string,
        blockId: string,
        name: string,
        parentPath: string[]
    ) => void;
    onHover: (sectionKey: string | null, blockId?: string | null) => void;
    collapseAllSignal?: number;
    isDraggingGlobal?: boolean;
}

export default function LayoutSectionRow({
    sectionKey,
    section,
    meta,
    position,
    isSelected,
    selectedBlockPath,
    themeBlocks,
    onSelect,
    onSelectBlock,
    onToggleDisabled,
    onRemoveBlock,
    onDuplicateBlock,
    onToggleBlockDisabled,
    onAddBlock,
    onOpenAddBlockModal,
    onRenameBlock,
    onHover,
    collapseAllSignal = 0,
    isDraggingGlobal = false,
}: LayoutSectionRowProps) {
    const isDisabled = !!section.disabled;
    const name = section._name || meta?.schema?.name || sectionKey;

    const sectionSchema = meta?.schema;
    const sectionRawBlocks = useMemo(
        () => (sectionSchema?.blocks as any[]) || [],
        [sectionSchema?.blocks]
    );

    const canAddBlocks = useMemo(
        () => canShowAddBlock(sectionRawBlocks),
        [sectionRawBlocks]
    );

    const addableBlockTypes = useMemo(
        () => getAddableBlockTypes(sectionRawBlocks, themeBlocks),
        [sectionRawBlocks, themeBlocks]
    );

    const blockOrder: string[] =
        section.order || Object.keys(section.blocks || {});
    const hasBlocks = blockOrder.length > 0;

    const [expanded, setExpanded] = useState(true);
    const expandedRef = useRef(expanded);
    useEffect(() => {
        expandedRef.current = expanded;
    }, [expanded]);

    // Collapse all signal
    const prevCollapseSignalRef = useRef(collapseAllSignal);
    useEffect(() => {
        if (collapseAllSignal !== prevCollapseSignalRef.current) {
            prevCollapseSignalRef.current = collapseAllSignal;
            setExpanded(false);
        }
    }, [collapseAllSignal]);

    // Auto-expand when a child block is selected
    useEffect(() => {
        if (isSelected && selectedBlockPath.length > 0) {
            setExpanded(true);
        }
    }, [isSelected, selectedBlockPath]);

    const PositionIcon = position === "top" ? PanelTop : PanelBottom;

    // Debounced hover
    const hoverTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const handleMouseEnter = useCallback(() => {
        hoverTimerRef.current = setTimeout(() => {
            onHover(sectionKey);
        }, 60);
    }, [onHover, sectionKey]);
    const handleMouseLeave = useCallback(() => {
        if (hoverTimerRef.current) {
            clearTimeout(hoverTimerRef.current);
            hoverTimerRef.current = null;
        }
        onHover(null);
    }, [onHover]);
    useEffect(() => {
        return () => {
            if (hoverTimerRef.current) clearTimeout(hoverTimerRef.current);
        };
    }, []);

    const handleAddBlock = useCallback(() => {
        if (!canAddBlocks) return;
        const types = addableBlockTypes;
        if (types.length === 1) {
            const bt = types[0];
            const defaults: Record<string, any> = {};
            (bt.settings || []).forEach((s: any) => {
                if (s.default !== undefined) defaults[s.id] = s.default;
            });
            onAddBlock(sectionKey, bt.type, defaults, null, []);
        } else {
            onOpenAddBlockModal(types, sectionKey, []);
        }
        setExpanded(true);
    }, [
        canAddBlocks,
        addableBlockTypes,
        sectionKey,
        onAddBlock,
        onOpenAddBlockModal,
    ]);

    return (
        <div data-layout-section-key={sectionKey}>
            {/* Section header row */}
            <div
                className={cn(
                    "group flex items-center px-2 py-[8px] cursor-pointer transition-colors select-none",
                    isDisabled && "opacity-50"
                )}
                onClick={() => onSelect(sectionKey)}
                onMouseEnter={handleMouseEnter}
                onMouseLeave={handleMouseLeave}
            >
                {/* Expand chevron */}
                <button
                    onClick={(e) => {
                        e.stopPropagation();
                        if (hasBlocks || canAddBlocks)
                            setExpanded((v) => !v);
                    }}
                    className={cn(
                        "w-4 h-4 flex items-center justify-center shrink-0 mr-0.5 transition-colors rounded",
                        hasBlocks || canAddBlocks
                            ? "text-gray-400 hover:text-gray-600"
                            : "text-transparent cursor-default"
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

                {/* Position icon */}
                <div className="w-4 h-4 flex items-center justify-center shrink-0 mr-2">
                    <PositionIcon
                        size={14}
                        strokeWidth={1.5}
                        className={cn(
                            isSelected && selectedBlockPath.length === 0
                                ? "text-blue-500"
                                : "text-gray-400 group-hover:text-gray-600 transition-colors"
                        )}
                    />
                </div>

                {/* Section name */}
                <span
                    className={cn(
                        "flex-1 text-xs font-semibold truncate transition-colors",
                        isSelected && selectedBlockPath.length === 0
                            ? "text-blue-600"
                            : "text-gray-700 group-hover:text-gray-900"
                    )}
                >
                    {name}
                </span>

                {/* Toggle visibility */}
                <button
                    title={isDisabled ? "Show section" : "Hide section"}
                    onClick={(e) => {
                        e.stopPropagation();
                        onToggleDisabled(sectionKey);
                    }}
                    className={cn(
                        "p-1 rounded transition-colors opacity-0 group-hover:opacity-100",
                        isDisabled
                            ? "text-gray-400 hover:text-blue-500 hover:bg-blue-100 !opacity-100"
                            : "text-gray-400 hover:text-gray-600 hover:bg-gray-200"
                    )}
                >
                    {isDisabled ? (
                        <EyeOff size={13} strokeWidth={1.5} />
                    ) : (
                        <Eye size={13} strokeWidth={1.5} />
                    )}
                </button>
            </div>

            {/* Expandable block tree */}
            {(hasBlocks || canAddBlocks) && expanded && (
                <div className="bg-white pb-0.5">
                    <SortableContext
                        items={blockOrder}
                        strategy={verticalListSortingStrategy}
                    >
                        {blockOrder.map((blockId) => {
                            const block = section.blocks?.[blockId];
                            if (!block) return null;
                            return (
                                <BlockItem
                                    key={blockId}
                                    blockId={blockId}
                                    block={block}
                                    parentRawBlocks={sectionRawBlocks}
                                    sectionId={sectionKey}
                                    siblingOrder={blockOrder}
                                    selectedBlockPath={selectedBlockPath}
                                    themeBlocks={themeBlocks}
                                    onSelectBlock={onSelectBlock}
                                    onRemoveBlock={onRemoveBlock}
                                    onDuplicateBlock={onDuplicateBlock}
                                    onToggleBlockDisabled={
                                        onToggleBlockDisabled
                                    }
                                    onAddBlock={onAddBlock}
                                    onHover={onHover}
                                    onOpenAddBlockModal={onOpenAddBlockModal}
                                    onRenameBlock={onRenameBlock}
                                    isDraggingGlobal={isDraggingGlobal}
                                    collapseAllSignal={collapseAllSignal}
                                    depth={1}
                                />
                            );
                        })}
                    </SortableContext>

                    {canAddBlocks && (
                        <AddBlockRow depth={1} onAdd={handleAddBlock} />
                    )}
                </div>
            )}
        </div>
    );
}
