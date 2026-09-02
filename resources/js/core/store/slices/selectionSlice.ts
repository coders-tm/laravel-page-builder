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
