import { StateCreator } from "zustand";
import { PageBuilderState } from "../useStore";
import { getThemeConfig } from "@/config";
import { parsePresetBlocks } from "@/core/utils/blocks";

export interface SectionSlice {
    updateSectionSettings: (
        sectionId: string,
        settingsPatch: Record<string, any>
    ) => void;
    addSection: (
        type: string,
        schema: any,
        insertIndex?: number | null,
        presetIndex?: number
    ) => string;
    removeSection: (sectionId: string) => void;
    reorderSections: (fromIndex: number, toIndex: number) => void;
    duplicateSection: (sectionId: string) => string;
    toggleSectionDisabled: (sectionId: string) => void;
    renameSectionInstance: (sectionId: string, name: string) => void;
}

export const createSectionSlice: StateCreator<
    PageBuilderState,
    [["zustand/immer", never]],
    [],
    SectionSlice
> = (set, get) => ({
    /**
     * Merge a settings patch into any section (page or layout) — they all
     * live in currentPage.sections now.
     */
    updateSectionSettings: (sectionId, patch) =>
        set((state) => {
            const sec = state.currentPage?.sections[sectionId];
            if (!sec) return;
            sec.settings = { ...sec.settings, ...patch };
        }),

    addSection: (type, sectionMeta, insertIndex = null, presetIndex = 0) => {
        const schema = (sectionMeta as any).schema || sectionMeta;
        const sectionId = `${type}_${Date.now()}`;

        const settings: any = {};
        (schema.settings || []).forEach((s: any) => {
            if (s.default !== undefined) settings[s.id] = s.default;
        });

        const blocks: any = {};
        const blockOrder: string[] = [];
        const preset = (schema.presets || [])[presetIndex] || (schema.presets || [])[0];

        if (preset) {
            if (preset.settings) {
                Object.assign(settings, preset.settings);
            }

            if (Array.isArray(preset.blocks)) {
                const result = parsePresetBlocks(
                    preset.blocks,
                    "",
                    get().blocks,
                    (pbType, prefix, i) => `${pbType}_${Date.now()}_${prefix}${i}`
                );
                Object.assign(blocks, result.parsedBlocks);
                blockOrder.push(...result.parsedOrder);
            }
        }

        set((state) => {
            if (!state.currentPage) return;
            if (insertIndex !== null) {
                state.currentPage.order.splice(insertIndex, 0, sectionId);
            } else {
                state.currentPage.order.push(sectionId);
            }
            state.currentPage.sections[sectionId] = {
                type,
                settings,
                blocks,
                order: blockOrder,
            };
        });
        return sectionId;
    },

    /**
     * Remove a page section.  Layout sections (layout: true) are structural
     * and cannot be removed through this action.
     */
    removeSection: (sectionId) =>
        set((state) => {
            if (!state.currentPage) return;
            const sec = state.currentPage.sections[sectionId];
            if (!sec || sec.layout) return; // guard: never remove layout sections
            delete state.currentPage.sections[sectionId];
            state.currentPage.order = state.currentPage.order.filter(
                (id) => id !== sectionId
            );
            if (state.selectedSection === sectionId) {
                state.selectedSection = null;
                state.selectedBlock = null;
                state.selectedBlockPath = [];
            }
        }),

    duplicateSection: (sectionId) => {
        let newId = "";
        set((state) => {
            const original = state.currentPage?.sections[sectionId];
            if (!original || !state.currentPage || original.layout) return;

            newId = `${original.type}_${Date.now()}`;
            const newBlocks: any = {};
            const newBlockOrder: string[] = [];

            (original.order || []).forEach((bid: string) => {
                const newBid = `${bid}_copy_${Date.now()}`;
                newBlocks[newBid] = JSON.parse(
                    JSON.stringify(original.blocks?.[bid] || {})
                );
                newBlockOrder.push(newBid);
            });

            const clonedSection = {
                ...JSON.parse(JSON.stringify(original)),
                blocks: newBlocks,
                order: newBlockOrder,
            };

            const order = state.currentPage.order;
            const idx = order.indexOf(sectionId);
            order.splice(idx + 1, 0, newId);
            state.currentPage.sections[newId] = clonedSection;
        });
        return newId;
    },

    reorderSections: (fromIndex, toIndex) =>
        set((state) => {
            if (!state.currentPage) return;
            const flatOrder = state.currentPage.order;
            const sections = state.currentPage.sections;

            // fromIndex / toIndex are positions within the page-only
            // (non-layout) sub-array that the DnD context exposes.
            // Translate them to positions inside the full flat order.
            const pageIds = flatOrder.filter(
                (id) => !(sections[id] as any)?.layout
            );
            const movedId = pageIds[fromIndex];
            const targetId = pageIds[toIndex];
            if (!movedId || !targetId || movedId === targetId) return;

            const fromFlat = flatOrder.indexOf(movedId);
            const toFlat = flatOrder.indexOf(targetId);
            const [moved] = flatOrder.splice(fromFlat, 1);
            flatOrder.splice(toFlat, 0, moved);
        }),

    /**
     * Toggle disabled for any section (page or layout).
     */
    toggleSectionDisabled: (sectionId) =>
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (section) section.disabled = !section.disabled;
        }),

    /**
     * Rename any section (page or layout).
     */
    renameSectionInstance: (sectionId, name) =>
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;
            if (name.trim()) {
                section._name = name.trim();
            } else {
                delete section._name;
            }
        }),
});
