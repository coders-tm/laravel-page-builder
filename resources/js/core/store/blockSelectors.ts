/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { useStore } from "./useStore"

export const useAppState = () => useStore((state) => state)

export const useCurrentPage = () => useStore((state) => state.currentPage)
export const useSectionsData = () => useStore((state) => state.sections)
export const useThemeBlocks = () => useStore((state) => state.blocks)

export const useSelectedSectionId = () => useStore((state) => state.selectedSection)
export const useSelectedBlockId = () => useStore((state) => state.selectedBlock)

export const useSectionInstance = (sectionId: string | null) =>
  useStore((state) => (sectionId ? state.currentPage?.sections[sectionId] : null))
