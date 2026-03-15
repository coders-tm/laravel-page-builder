import React, {
  useState,
  useCallback,
  useEffect,
  useMemo,
  useRef,
} from "react";
import {
  DndContext,
  closestCenter,
  PointerSensor,
  useSensor,
  useSensors,
} from "@dnd-kit/core";
import {
  SortableContext,
  verticalListSortingStrategy,
  arrayMove,
} from "@dnd-kit/sortable";
import { Plus, ChevronsDownUp } from "lucide-react";
import AddBlockModal from "./AddBlockModal";
import { getNestedBlock, useStore } from "@/core/store/useStore";
import { useEditorInstance } from "@/core/editorContext";
import { useEditorLayout } from "@/hooks/useEditorLayout";
import { useEditorNavigation } from "@/hooks/useEditorNavigation";
import api from "@/services/api";
import { BlockSchema, SectionInstance } from "@/types/page-builder";
import SortableSectionRow from "./layout/SortableSectionRow";
import LayoutSectionRow from "./layout/LayoutSectionRow";

/* ── LayoutPanel ──────────────────────────────────────────────────────── */
export default function LayoutPanel() {
  const editor = useEditorInstance();
  const layout = useEditorLayout();
  const currentPage = useStore((s) => s.currentPage);
  const sections = useStore((s) => s.sections);
  const themeBlocks = useStore((s) => s.blocks);

  const {
    slug,
    selectedSection,
    blockPath: selectedBlockPath,
  } = useEditorNavigation();

  // Tracks whether the *active* drag is a section drag (not a block drag).
  // Used to collapse all sections during section reorder without collapsing
  // sections when a block is being sorted inside them.
  const [isDraggingSections, setIsDraggingSections] = useState(false);
  // Incrementing counter — each increment signals all sections/blocks to collapse.
  const [collapseAllSignal, setCollapseAllSignal] = useState(0);

  const scrollListRef = useRef<HTMLDivElement>(null);

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
  );

  const blockPreviewUrl = useMemo(() => {
    if (!slug) return "about:blank";
    const url = new URL(api.getPreviewUrl(slug));
    url.searchParams.set("pb-editor", "1");
    url.searchParams.set("pb-preview", "1");
    url.searchParams.set("source", "block");
    return url.toString();
  }, [slug]);

  const openAddBlockModal = useCallback(
    (
      types: BlockSchema[],
      sectionId: string,
      parentPath: string[],
      afterBlockId: string | null = null,
    ) => {
      editor.layout.openAddBlockModal(
        types,
        sectionId,
        parentPath,
        afterBlockId,
      );
    },
    [editor],
  );

  const handleSelectSection = useCallback(
    (sectionId: string) => {
      editor.selectSection(sectionId);
    },
    [editor],
  );

  const handleSelectBlock = useCallback(
    (sectionId: string, path: string[]) => {
      editor.selectBlock(sectionId, path);
    },
    [editor],
  );

  const handleAddBlock = useCallback(
    (
      sectionId: string,
      type: string,
      defaults: Record<string, any>,
      afterBlockId: string | null = null,
      parentPath: string[] = [],
    ) => {
      const blockId = editor.addBlock(
        sectionId,
        type,
        defaults,
        afterBlockId,
        parentPath,
      );
      editor.selectBlock(sectionId, [...parentPath, blockId]);
    },
    [editor],
  );

  const handleAddBlockFromModal = useCallback(
    (type: string) => {
      const modal = layout.addBlockModal;
      if (!modal?.isOpen || !modal.sectionId) return;
      const matched = modal.blockTypes.find((t) => t.type === type);
      if (!matched) return;
      const defaults: Record<string, any> = {};
      (matched.settings || []).forEach((s: any) => {
        if (s.default !== undefined) defaults[s.id] = s.default;
      });
      handleAddBlock(
        modal.sectionId,
        type,
        defaults,
        modal.afterBlockId ?? null,
        modal.parentPath,
      );
      editor.layout.closeAddBlockModal();
    },
    [layout.addBlockModal, handleAddBlock, editor],
  );

  const handleRemoveSection = useCallback(
    (sectionId: string) => {
      if (selectedSection === sectionId) {
        editor.clearSelection();
      }
      editor.removeSection(sectionId);
    },
    [selectedSection, editor],
  );

  const handleDuplicateSection = useCallback(
    (sectionId: string) => {
      const newId = editor.sections.duplicate(sectionId);
      if (newId) {
        editor.selectSection(newId);
      }
    },
    [editor],
  );

  const handleReorderSections = useCallback(
    (fromIndex: number, toIndex: number) => {
      editor.sections.reorder(fromIndex, toIndex);
    },
    [editor],
  );

  const handleReorderBlocks = useCallback(
    (sectionId: string, order: string[], parentPath: string[] = []) => {
      editor.blocks.reorder(sectionId, order, parentPath);
    },
    [editor],
  );

  const handleMoveBlock = useCallback(
    (
      fromSectionId: string,
      toSectionId: string,
      blockId: string,
      fromPath: string[],
      toPath: string[],
      toIndex: number,
    ) => {
      editor.blocks.move(
        fromSectionId,
        toSectionId,
        blockId,
        fromPath,
        toPath,
        toIndex,
      );
    },
    [editor],
  );

  const handleToggleSectionDisabled = useCallback(
    (sectionId: string) => {
      editor.sections.toggleDisabled(sectionId);
    },
    [editor],
  );

  const handleRemoveBlock = useCallback(
    (sectionId: string, blockId: string, parentPath: string[] = []) => {
      const activeBlockId =
        selectedBlockPath.length > 0
          ? selectedBlockPath[selectedBlockPath.length - 1]
          : null;
      const wasSelected =
        selectedSection === sectionId && activeBlockId === blockId;

      editor.removeBlock(sectionId, blockId, parentPath);

      if (wasSelected) {
        if (parentPath.length > 0) {
          editor.selectBlock(sectionId, parentPath);
        } else {
          editor.selectSection(sectionId);
        }
      }
    },
    [selectedSection, selectedBlockPath, editor],
  );

  const handleDuplicateBlock = useCallback(
    (sectionId: string, blockId: string, parentPath: string[] = []) => {
      editor.blocks.duplicate(sectionId, blockId, parentPath);
    },
    [editor],
  );

  const handleToggleBlockDisabled = useCallback(
    (sectionId: string, blockId: string, parentPath: string[] = []) => {
      editor.blocks.toggleDisabled(sectionId, blockId, parentPath);
    },
    [editor],
  );

  const handleRenameSection = useCallback(
    (sectionId: string, name: string) => {
      editor.renameSection(sectionId, name);
    },
    [editor],
  );

  const handleRenameBlock = useCallback(
    (
      sectionId: string,
      blockId: string,
      name: string,
      parentPath: string[] = [],
    ) => {
      editor.renameBlock(sectionId, blockId, name, parentPath);
    },
    [editor],
  );

  const handleOpenAddSectionAtEdge = useCallback(
    (sectionId: string, edge: "top" | "bottom") => {
      editor.layout.openAddSectionModal(
        edge === "top" ? "before" : "after",
        sectionId,
      );
    },
    [editor],
  );

  const handleHover = useCallback(
    (sectionId: string | null, blockId: string | null = null) => {
      editor.interaction.hover(sectionId, blockId);
    },
    [editor],
  );

  // Scroll the layout panel row into view when selected from canvas.
  useEffect(() => {
    if (!selectedSection || !scrollListRef.current) return;
    const row = scrollListRef.current.querySelector(
      `[data-section-id="${selectedSection}"]`,
    );
    if (row) {
      row.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  }, [selectedSection]);

  if (!currentPage) {
    return (
      <div className="flex flex-col items-center justify-center flex-1 p-6 text-center">
        <div className="text-4xl mb-3">📄</div>
        <p className="text-xs text-gray-400">Select a page to start editing</p>
      </div>
    );
  }

  const order = currentPage.order || [];
  const pageSections = currentPage.sections || {};

  // ── Page-only sub-order (for DnD context) ───────────────────────────
  // The flat `order` contains layout section IDs pinned at the head/tail.
  // The DnD sortable context must only see moveable page-section IDs.
  const pageOnlyOrder = order.filter(
    (id) => !(pageSections[id] as SectionInstance)?.layout,
  );

  const handleDragResult = ({ active, over }) => {
    if (!over || active.id === over.id) return;

    const activeData = active.data.current;
    const overData = over.data.current;

    // 1. Handle Section Reordering
    if (!activeData?.type || activeData.type === "section") {
      const oldIdx = pageOnlyOrder.indexOf(active.id);
      const newIdx = pageOnlyOrder.indexOf(over.id);
      if (oldIdx !== -1 && newIdx !== -1) {
        handleReorderSections(oldIdx, newIdx);
      }
    }
    // 2. Handle Block Reordering / Movement
    else if (activeData.type === "block") {
      const {
        sectionId: fromSectionId,
        blockId,
        parentPath: fromPath,
      } = activeData;
      const toSectionId = overData?.sectionId || (over.id as string);
      const toPath = overData?.parentPath || [];

      // Same parent? → simple reorder (no server round-trip needed).
      const isSameParent =
        fromSectionId === toSectionId &&
        fromPath.join(",") === toPath.join(",");

      if (isSameParent) {
        // Resolve the current order for this parent
        const parentBlock =
          fromPath.length > 0
            ? getNestedBlock(pageSections[fromSectionId]?.blocks, fromPath)
            : pageSections[fromSectionId];

        const currentOrder: string[] =
          parentBlock?.order || Object.keys(parentBlock?.blocks || {});
        const oldIdx = currentOrder.indexOf(active.id as string);
        const newIdx = currentOrder.indexOf(over.id as string);

        if (oldIdx !== -1 && newIdx !== -1) {
          const newOrder = [...currentOrder];
          const [moved] = newOrder.splice(oldIdx, 1);
          newOrder.splice(newIdx, 0, moved);
          handleReorderBlocks(fromSectionId, newOrder, fromPath);
        }
        return;
      }

      // Cross-parent or cross-section move.
      const toSection = pageSections[toSectionId];
      if (!toSection) return;

      const targetOrder =
        toPath.length > 0
          ? getNestedBlock(toSection.blocks, toPath)?.order
          : toSection.order;

      if (targetOrder) {
        let toIndex = targetOrder.indexOf(over.id);
        if (toIndex === -1) {
          toIndex = targetOrder.length;
        }

        handleMoveBlock(
          fromSectionId,
          toSectionId,
          blockId,
          fromPath,
          toPath,
          toIndex,
        );
      }
    }
  };

  return (
    <div className="flex flex-col h-full bg-white">
      {/* Page title */}

      <div className="px-3 py-3 border-b border-gray-200 bg-white flex-shrink-0 flex items-center justify-between gap-2">
        <div className="min-w-0">
          <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">
            Page
          </p>
          <h2 className="text-sm font-semibold text-gray-800 truncate">
            {currentPage.title || "Untitled Page"}
          </h2>
        </div>
        <button
          title="Collapse all"
          onClick={() => setCollapseAllSignal((s) => s + 1)}
          className="shrink-0 p-1 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
        >
          <ChevronsDownUp size={14} strokeWidth={2} />
        </button>
      </div>

      {/* Sortable section list */}
      <div
        ref={scrollListRef}
        className="flex-1 overflow-y-auto sidebar-scroll"
      >
        {/*
         * Three-zone render of the flat order:
         *   1. Layout Top — pinned header layout sections
         *   2. Page Sections — sortable page content sections
         *   3. Layout Bottom — pinned footer layout sections
         *
         * Each zone gets a small label divider for visual clarity.
         *
         * DndContext wraps ALL zones so that block drag-and-drop
         * works inside layout (header/footer) sections too.
         */}
        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragStart={({ active }) => {
            const isSectionDrag =
              !active.data.current?.type ||
              active.data.current?.type === "section";
            setIsDraggingSections(isSectionDrag);
            if (isSectionDrag) {
              setCollapseAllSignal((s) => s + 1);
            }
            editor.layout.startDrag();
          }}
          onDragOver={({ active, over }) => {
            if (!over) return;
            const activeData = active.data.current;

            // ── 1. Section reordering sync ──
            if (!activeData?.type || activeData.type === "section") {
              const oldIdx = pageOnlyOrder.indexOf(active.id as string);
              const newIdx = pageOnlyOrder.indexOf(over.id as string);
              if (oldIdx !== -1 && newIdx !== -1) {
                editor.preview.reorderSections(
                  arrayMove(pageOnlyOrder, oldIdx, newIdx),
                );
              }
            }
            // ── 2. Block reordering sync ──
            else if (activeData.type === "block") {
              const overData = over.data.current;
              if (overData?.type === "block") {
                const { sectionId: fromSectionId, parentPath: fromPath } =
                  activeData;
                const { sectionId: toSectionId, parentPath: toPath } = overData;

                const isSameParent =
                  fromSectionId === toSectionId &&
                  fromPath.join(",") === toPath.join(",");

                if (isSameParent) {
                  const parentBlock =
                    fromPath.length > 0
                      ? getNestedBlock(
                          pageSections[fromSectionId]?.blocks,
                          fromPath,
                        )
                      : pageSections[fromSectionId];

                  const currentOrder: string[] =
                    parentBlock?.order ||
                    Object.keys(parentBlock?.blocks || {});
                  const oldIdx = currentOrder.indexOf(active.id as string);
                  const newIdx = currentOrder.indexOf(over.id as string);

                  if (oldIdx !== -1 && newIdx !== -1) {
                    editor.preview.reorderBlocks(
                      fromSectionId,
                      arrayMove(currentOrder, oldIdx, newIdx),
                      fromPath.length > 0
                        ? fromPath[fromPath.length - 1]
                        : null,
                    );
                  }
                }
              }
            }
          }}
          onDragEnd={(event) => {
            setIsDraggingSections(false);
            handleDragResult(event);
            editor.layout.endDrag();

            // Final sync to ensure preview matches store even on cancel/no-change
            editor.preview.reorderSections(pageOnlyOrder);

            const activeData = event.active.data.current;
            if (activeData?.type === "block") {
              const { sectionId, parentPath } = activeData;
              const section = pageSections[sectionId];
              if (section) {
                const parent =
                  parentPath.length > 0
                    ? getNestedBlock(section.blocks, parentPath)
                    : section;
                const order =
                  parent?.order || Object.keys(parent?.blocks || {});

                editor.preview.reorderBlocks(
                  sectionId,
                  order,
                  parentPath.length > 0
                    ? parentPath[parentPath.length - 1]
                    : null,
                );
              }
            }
          }}
          onDragCancel={() => {
            setIsDraggingSections(false);
            editor.layout.endDrag();
          }}
        >
          {/* ── Zone 1: Layout Top ────────────────────────────── */}
          {(() => {
            const topIds = order.filter((id) => {
              const s = pageSections[id] as SectionInstance;
              return s?.layout && s.layoutZone !== "footer";
            });
            if (topIds.length === 0) return null;
            return (
              <div>
                <div className="px-3 pt-2.5 pb-1">
                  <span className="text-[9px] font-semibold text-gray-400 uppercase tracking-widest">
                    Header
                  </span>
                </div>
                {topIds.map((sectionId) => {
                  const section = pageSections[sectionId] as SectionInstance;
                  if (!section) return null;
                  const meta = sections[section.type];
                  return (
                    <LayoutSectionRow
                      key={sectionId}
                      sectionKey={sectionId}
                      section={section}
                      meta={meta}
                      position="top"
                      isSelected={selectedSection === sectionId}
                      selectedBlockPath={
                        selectedSection === sectionId ? selectedBlockPath : []
                      }
                      themeBlocks={themeBlocks}
                      onSelect={handleSelectSection}
                      onSelectBlock={handleSelectBlock}
                      onToggleDisabled={handleToggleSectionDisabled}
                      onRemoveBlock={handleRemoveBlock}
                      onDuplicateBlock={handleDuplicateBlock}
                      onToggleBlockDisabled={handleToggleBlockDisabled}
                      onAddBlock={handleAddBlock}
                      onOpenAddBlockModal={openAddBlockModal}
                      onRenameBlock={handleRenameBlock}
                      onHover={handleHover}
                      collapseAllSignal={collapseAllSignal}
                      isDraggingGlobal={isDraggingSections}
                    />
                  );
                })}
              </div>
            );
          })()}

          {/* ── Zone 2: Page Sections (sortable) ──────────────── */}
          <div>
            <div className="px-3 pt-2.5 pb-1 border-t border-gray-100">
              <span className="text-[9px] font-semibold text-gray-400 uppercase tracking-widest">
                Page sections
              </span>
            </div>

            <SortableContext
              items={pageOnlyOrder}
              strategy={verticalListSortingStrategy}
            >
              {pageOnlyOrder.length === 0 && (
                <p className="text-xs text-gray-400 text-center py-6 px-4">
                  No page sections yet.
                </p>
              )}
              {pageOnlyOrder.map((sectionId) => {
                const section = pageSections[sectionId] as SectionInstance;
                if (!section) return null;
                const meta = sections[section.type];
                return (
                  <SortableSectionRow
                    key={sectionId}
                    sectionId={sectionId}
                    section={section}
                    meta={meta}
                    themeBlocks={themeBlocks}
                    isSelected={selectedSection === sectionId}
                    selectedBlockPath={
                      selectedSection === sectionId ? selectedBlockPath : []
                    }
                    onSelect={handleSelectSection}
                    onSelectBlock={handleSelectBlock}
                    onRemove={handleRemoveSection}
                    onDuplicate={handleDuplicateSection}
                    onToggleSectionDisabled={handleToggleSectionDisabled}
                    onRemoveBlock={handleRemoveBlock}
                    onDuplicateBlock={handleDuplicateBlock}
                    onToggleBlockDisabled={handleToggleBlockDisabled}
                    onAddBlock={handleAddBlock}
                    onOpenAddBlockModal={openAddBlockModal}
                    onOpenAddSectionAtEdge={handleOpenAddSectionAtEdge}
                    onRenameSection={handleRenameSection}
                    onRenameBlock={handleRenameBlock}
                    onHover={handleHover}
                    isDraggingGlobal={isDraggingSections}
                    collapseAllSignal={collapseAllSignal}
                  />
                );
              })}
            </SortableContext>

            {/* Inline "Add section" row — sits after all page sections */}
            <div
              onClick={() => editor.layout.openAddSectionModal()}
              className="flex items-center gap-1.5 px-3 py-3 cursor-pointer group transition-colors border-t border-gray-100"
            >
              <Plus
                size={13}
                strokeWidth={2.5}
                className="text-gray-400 group-hover:text-blue-500 shrink-0 transition-colors"
              />
              <span className="text-[11px] text-gray-400 group-hover:text-blue-500 font-medium transition-colors">
                Add section
              </span>
            </div>
          </div>

          {/* ── Zone 3: Layout Bottom ──────────────────────────── */}
          {(() => {
            const bottomIds = order.filter((id) => {
              const s = pageSections[id] as SectionInstance;
              return s?.layout && s.layoutZone === "footer";
            });
            if (bottomIds.length === 0) return null;
            return (
              <div>
                <div className="px-3 pt-2.5 pb-1 border-t border-gray-100">
                  <span className="text-[9px] font-semibold text-gray-400 uppercase tracking-widest">
                    Footer
                  </span>
                </div>
                {bottomIds.map((sectionId) => {
                  const section = pageSections[sectionId] as SectionInstance;
                  if (!section) return null;
                  const meta = sections[section.type];
                  return (
                    <LayoutSectionRow
                      key={sectionId}
                      sectionKey={sectionId}
                      section={section}
                      meta={meta}
                      position="bottom"
                      isSelected={selectedSection === sectionId}
                      selectedBlockPath={
                        selectedSection === sectionId ? selectedBlockPath : []
                      }
                      themeBlocks={themeBlocks}
                      onSelect={handleSelectSection}
                      onSelectBlock={handleSelectBlock}
                      onToggleDisabled={handleToggleSectionDisabled}
                      onRemoveBlock={handleRemoveBlock}
                      onDuplicateBlock={handleDuplicateBlock}
                      onToggleBlockDisabled={handleToggleBlockDisabled}
                      onAddBlock={handleAddBlock}
                      onOpenAddBlockModal={openAddBlockModal}
                      onRenameBlock={handleRenameBlock}
                      onHover={handleHover}
                      collapseAllSignal={collapseAllSignal}
                      isDraggingGlobal={isDraggingSections}
                    />
                  );
                })}
              </div>
            );
          })()}
        </DndContext>
      </div>

      {/* Add-block modal */}
      <AddBlockModal
        isOpen={layout.addBlockModal.isOpen}
        previewUrl={blockPreviewUrl}
        blockTypes={layout.addBlockModal.blockTypes}
        onAdd={handleAddBlockFromModal}
        onClose={() => editor.layout.closeAddBlockModal()}
      />
    </div>
  );
}
