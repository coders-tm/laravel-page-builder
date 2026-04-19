import type { BlockSchema } from "@/types/page-builder";

/**
 * Recursively parses preset blocks from a schema and correctly resolves
 * default settings from the global block registry.
 * 
 * Used during both additions (store) and preview generation to ensure 
 * nested child block payloads are completely filled out.
 */
export function parsePresetBlocks(
    presetBlocks: any[],
    idPrefix: string,
    blockRegistry: Record<string, any> | BlockSchema[],
    idGenerator: (type: string, prefix: string, index: number) => string
): { parsedBlocks: Record<string, any>; parsedOrder: string[] } {
    const parsedBlocks: Record<string, any> = {};
    const parsedOrder: string[] = [];

    const isArrayRegistry = Array.isArray(blockRegistry);

    presetBlocks.forEach((pb: any, i: number) => {
        const type = pb.type || "block";
        const blockId = idGenerator(type, idPrefix, i);

        const { parsedBlocks: childBlocks, parsedOrder: childOrder } = Array.isArray(pb.blocks)
            ? parsePresetBlocks(pb.blocks, `${idPrefix}${i}_`, blockRegistry, idGenerator)
            : { parsedBlocks: {}, parsedOrder: [] };

        const childSchema = isArrayRegistry 
            ? ((blockRegistry as BlockSchema[]).find(t => t.type === type) || {})
            : ((blockRegistry as Record<string, any>)[type]?.schema || {});

        const childDefaults: Record<string, any> = {};
        (childSchema.settings || []).forEach((s: any) => {
           if (s.default !== undefined) childDefaults[s.id] = s.default;
        });

        parsedBlocks[blockId] = {
            type,
            settings: { ...childDefaults, ...(pb.settings || {}) },
            blocks: childBlocks,
            order: childOrder,
        };
        parsedOrder.push(blockId);
    });

    return { parsedBlocks, parsedOrder };
}
