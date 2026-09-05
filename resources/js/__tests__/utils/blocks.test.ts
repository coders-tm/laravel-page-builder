/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, vi } from "vitest"
import { parsePresetBlocks } from "@/core/utils/blocks"

const blockRegistry = {
  text: { schema: { type: "text", settings: [{ id: "content", default: "Default" }] } },
  image: { schema: { type: "image", settings: [{ id: "src", default: "" }] } },
}

const idGen = (type: string, prefix: string, i: number) => `${type}_${prefix}${i}`

describe("parsePresetBlocks", () => {
  it("returns empty objects for an empty preset array", () => {
    const result = parsePresetBlocks([], "", blockRegistry, idGen)
    expect(result).toEqual({ parsedBlocks: {}, parsedOrder: [] })
  })

  it("parses flat preset blocks correctly", () => {
    const presets = [
      { type: "text", settings: { content: "Hello" } },
      { type: "image", settings: { src: "/img.jpg" } },
    ]
    const { parsedBlocks, parsedOrder } = parsePresetBlocks(presets, "", blockRegistry, idGen)

    expect(parsedOrder).toHaveLength(2)
    expect(parsedBlocks[parsedOrder[0]].type).toBe("text")
    expect(parsedBlocks[parsedOrder[0]].settings.content).toBe("Hello")
    expect(parsedBlocks[parsedOrder[1]].type).toBe("image")
    expect(parsedBlocks[parsedOrder[1]].settings.src).toBe("/img.jpg")
  })

  it("applies registry defaults when preset does not override them", () => {
    const presets = [{ type: "text" }]
    const { parsedBlocks, parsedOrder } = parsePresetBlocks(presets, "", blockRegistry, idGen)
    expect(parsedBlocks[parsedOrder[0]].settings.content).toBe("Default")
  })

  it("preset settings override registry defaults", () => {
    const presets = [{ type: "text", settings: { content: "Override" } }]
    const { parsedBlocks, parsedOrder } = parsePresetBlocks(presets, "", blockRegistry, idGen)
    expect(parsedBlocks[parsedOrder[0]].settings.content).toBe("Override")
  })

  it("parses nested preset blocks recursively", () => {
    const presets = [
      {
        type: "text",
        settings: { content: "Parent" },
        blocks: [{ type: "image", settings: { src: "/child.jpg" } }],
      },
    ]
    const { parsedBlocks, parsedOrder } = parsePresetBlocks(presets, "", blockRegistry, idGen)
    const parentBlock = parsedBlocks[parsedOrder[0]]
    expect(parentBlock.type).toBe("text")
    // Child blocks should be nested
    expect(Object.keys(parentBlock.blocks).length).toBeGreaterThan(0)
    const childId = parentBlock.order[0]
    expect(parentBlock.blocks[childId].type).toBe("image")
    expect(parentBlock.blocks[childId].settings.src).toBe("/child.jpg")
  })

  it("calls idGenerator with correct (type, prefix, index) arguments", () => {
    const spy = vi.fn((type: string, prefix: string, i: number) => `${type}_${prefix}${i}`)
    const presets = [{ type: "text" }, { type: "image" }]
    parsePresetBlocks(presets, "pfx_", blockRegistry, spy)
    expect(spy).toHaveBeenNthCalledWith(1, "text", "pfx_", 0)
    expect(spy).toHaveBeenNthCalledWith(2, "image", "pfx_", 1)
  })

  it("works with an array-format blockRegistry", () => {
    const arrayRegistry = [
      { type: "text", settings: [{ id: "content", default: "Array default" }] },
    ] as any
    const presets = [{ type: "text" }]
    const { parsedBlocks, parsedOrder } = parsePresetBlocks(presets, "", arrayRegistry, idGen)
    expect(parsedBlocks[parsedOrder[0]].settings.content).toBe("Array default")
  })
})
