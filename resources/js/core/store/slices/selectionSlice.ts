/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { StateCreator } from "zustand"

export interface SelectionSlice {
  selectedSection: string | null
  selectedBlock: string | null
  selectedBlockPath: string[]

  setSelectedSection: (id: string | null) => void
  setSelectedBlock: (id: string | null) => void
  setSelectedBlockPath: (path: string[]) => void
}

export const createSelectionSlice: StateCreator<SelectionSlice, [["zustand/immer", never]]> = (
  set,
) => ({
  selectedSection: null,
  selectedBlock: null,
  selectedBlockPath: [],

  setSelectedSection: (id) => set({ selectedSection: id }),
  setSelectedBlock: (id) => set({ selectedBlock: id }),
  setSelectedBlockPath: (path) => set({ selectedBlockPath: path }),
})
