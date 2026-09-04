/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { create } from "zustand"
import { immer } from "zustand/middleware/immer"

import {
  createPageSlice,
  createSelectionSlice,
  createSectionSlice,
  createBlockSlice,
  type PageSlice,
  type SelectionSlice,
  type SectionSlice,
  type BlockSlice,
} from "./slices"

/**
 * Walk a block tree following `path` and return the block at that depth.
 */
export function getNestedBlock(
  sectionBlocks: Record<string, any>,
  path: string[],
): Record<string, any> | undefined {
  if (path.length === 0) return undefined
  let node: any = sectionBlocks[path[0]]
  for (let i = 1; i < path.length; i++) {
    if (!node) return undefined
    node = node.blocks?.[path[i]]
  }
  return node
}

export type PageBuilderState = PageSlice & SelectionSlice & SectionSlice & BlockSlice

export const useStore = create<PageBuilderState>()(
  immer((...a) => ({
    ...createPageSlice(...a),
    ...createSelectionSlice(...a),
    ...createSectionSlice(...a),
    ...createBlockSlice(...a),
  })),
)
