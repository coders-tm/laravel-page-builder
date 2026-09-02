import { useStore } from "./useStore"

export const useAppState = () => useStore((state) => state)

export const useCurrentPage = () => useStore((state) => state.currentPage)
export const useSectionsData = () => useStore((state) => state.sections)
export const useThemeBlocks = () => useStore((state) => state.blocks)

export const useSelectedSectionId = () => useStore((state) => state.selectedSection)
export const useSelectedBlockId = () => useStore((state) => state.selectedBlock)

export const useSectionInstance = (sectionId: string | null) =>
  useStore((state) => (sectionId ? state.currentPage?.sections[sectionId] : null))
