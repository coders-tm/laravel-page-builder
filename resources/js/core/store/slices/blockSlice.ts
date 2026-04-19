import { StateCreator } from "zustand";
import { PageBuilderState, getNestedBlock } from "../useStore";

export interface BlockSlice {
    updateBlockSettings: (
        sectionId: string,
        blockId: string,
        settingsPatch: Record<string, any>,
        parentPath?: string[]
    ) => void;
    addBlock: (
        sectionId: string,
        blockType: string,
        defaultSettings: any,
        afterBlockId?: string | null,
        parentPath?: string[],
        initialBlocks?: Record<string, any>,
        initialOrder?: string[]
    ) => string;
    removeBlock: (
        sectionId: string,
        blockId: string,
        parentPath?: string[]
    ) => void;
    duplicateBlock: (
        sectionId: string,
        blockId: string,
        parentPath?: string[]
    ) => string;
    reorderBlocks: (
        sectionId: string,
        newBlockOrder: string[],
        parentPath?: string[]
    ) => void;
    toggleBlockDisabled: (
        sectionId: string,
        blockId: string,
        parentPath?: string[]
    ) => void;
    moveBlock: (
        fromSectionId: string,
        toSectionId: string,
        blockId: string,
        fromPath: string[],
        toPath: string[],
        toIndex: number
    ) => void;
    renameBlockInstance: (
        sectionId: string,
        blockId: string,
        name: string,
        parentPath?: string[]
    ) => void;
}

export const createBlockSlice: StateCreator<
    PageBuilderState,
    [["zustand/immer", never]],
    [],
    BlockSlice
> = (set) => ({
    updateBlockSettings: (sectionId, blockId, patch, parentPath = []) =>
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;

            if (parentPath.length > 0) {
                const parent = getNestedBlock(section.blocks, parentPath);
                if (parent && parent.blocks?.[blockId]) {
                    parent.blocks[blockId].settings = {
                        ...parent.blocks[blockId].settings,
                        ...patch,
                    };
                }
            } else {
                if (section.blocks?.[blockId]) {
                    section.blocks[blockId].settings = {
                        ...section.blocks[blockId].settings,
                        ...patch,
                    };
                }
            }
        }),

    addBlock: (
        sectionId,
        blockType,
        defaults,
        afterId = null,
        parentPath = [],
        initialBlocks = {},
        initialOrder = []
    ) => {
        const blockId = `${blockType}_${Date.now()}`;
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;

            const blockObj = { type: blockType, settings: defaults, blocks: initialBlocks, order: initialOrder };

            if (parentPath.length > 0) {
                const parent = getNestedBlock(section.blocks, parentPath);
                if (parent) {
                    parent.blocks = parent.blocks || {};
                    parent.blocks[blockId] = blockObj;
                    parent.order = parent.order || [];
                    if (afterId) {
                        const idx = parent.order.indexOf(afterId);
                        parent.order.splice(idx + 1, 0, blockId);
                    } else {
                        parent.order.push(blockId);
                    }
                }
            } else {
                section.blocks = section.blocks || {};
                section.blocks[blockId] = blockObj;
                section.order = section.order || [];
                if (afterId) {
                    const idx = section.order.indexOf(afterId);
                    section.order.splice(idx + 1, 0, blockId);
                } else {
                    section.order.push(blockId);
                }
            }
        });
        return blockId;
    },

    removeBlock: (sectionId, blockId, parentPath = []) =>
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;

            if (parentPath.length > 0) {
                const parent = getNestedBlock(section.blocks, parentPath);
                if (parent && parent.blocks) {
                    delete parent.blocks[blockId];
                    parent.order = (parent.order || []).filter(
                        (id: string) => id !== blockId
                    );
                }
            } else {
                delete section.blocks[blockId];
                section.order = (section.order || []).filter(
                    (id: string) => id !== blockId
                );
            }

            if (state.selectedBlock === blockId) {
                state.selectedBlock = null;
                state.selectedBlockPath = [];
            }
        }),

    duplicateBlock: (sectionId, blockId, parentPath = []) => {
        let newId = "";
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;

            let original: any = null;
            let parent: any = null;

            if (parentPath.length > 0) {
                parent = getNestedBlock(section.blocks, parentPath);
                original = parent?.blocks?.[blockId];
            } else {
                original = section.blocks?.[blockId];
            }

            if (!original) return;

            newId = `${original.type}_${Date.now()}`;
            const clonedBlock = JSON.parse(JSON.stringify(original));

            if (parentPath.length > 0 && parent && parent.blocks) {
                parent.blocks[newId] = clonedBlock;
                const idx = parent.order.indexOf(blockId);
                parent.order.splice(idx + 1, 0, newId);
            } else {
                section.blocks[newId] = clonedBlock;
                const idx = section.order.indexOf(blockId);
                section.order.splice(idx + 1, 0, newId);
            }
        });
        return newId;
    },

    reorderBlocks: (sectionId, newBlockOrder, parentPath = []) =>
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;

            if (parentPath.length > 0) {
                const parent = getNestedBlock(section.blocks, parentPath);
                if (parent) {
                    parent.order = newBlockOrder;
                }
            } else {
                section.order = newBlockOrder;
            }
        }),

    toggleBlockDisabled: (sectionId, blockId, parentPath = []) =>
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;

            if (parentPath.length > 0) {
                const parent = getNestedBlock(section.blocks, parentPath);
                if (parent && parent.blocks?.[blockId]) {
                    parent.blocks[blockId].disabled =
                        !parent.blocks[blockId].disabled;
                }
            } else {
                if (section.blocks?.[blockId]) {
                    section.blocks[blockId].disabled =
                        !section.blocks[blockId].disabled;
                }
            }
        }),

    moveBlock: (
        fromSectionId,
        toSectionId,
        blockId,
        fromPath,
        toPath,
        toIndex
    ) =>
        set((state) => {
            const fromSection = state.currentPage?.sections[fromSectionId];
            const toSection = state.currentPage?.sections[toSectionId];
            if (!fromSection || !toSection) return;

            // 1. Get the block data
            let blockData: any;
            if (fromPath.length > 0) {
                const parent = getNestedBlock(fromSection.blocks, fromPath);
                if (parent && parent.blocks?.[blockId]) {
                    blockData = JSON.parse(
                        JSON.stringify(parent.blocks[blockId])
                    );
                    delete parent.blocks[blockId];
                    parent.order = (parent.order || []).filter(
                        (id: any) => id !== blockId
                    );
                }
            } else {
                if (fromSection.blocks?.[blockId]) {
                    blockData = JSON.parse(
                        JSON.stringify(fromSection.blocks[blockId])
                    );
                    delete fromSection.blocks[blockId];
                    fromSection.order = (fromSection.order || []).filter(
                        (id: any) => id !== blockId
                    );
                }
            }

            if (!blockData) return;

            // 2. Insert into destination
            if (toPath.length > 0) {
                const parent = getNestedBlock(toSection.blocks, toPath);
                if (parent) {
                    parent.blocks = parent.blocks || {};
                    parent.blocks[blockId] = blockData;
                    parent.order = parent.order || [];
                    parent.order.splice(toIndex, 0, blockId);
                }
            } else {
                toSection.blocks = toSection.blocks || {};
                toSection.blocks[blockId] = blockData;
                toSection.order = toSection.order || [];
                toSection.order.splice(toIndex, 0, blockId);
            }
        }),

    renameBlockInstance: (sectionId, blockId, name, parentPath = []) =>
        set((state) => {
            const section = state.currentPage?.sections[sectionId];
            if (!section) return;
            let block: any;
            if (parentPath.length > 0) {
                const parent = getNestedBlock(section.blocks, parentPath);
                block = parent?.blocks?.[blockId];
            } else {
                block = section.blocks?.[blockId];
            }
            if (!block) return;
            if (name.trim()) {
                block._name = name.trim();
            } else {
                delete block._name;
            }
        }),
});
