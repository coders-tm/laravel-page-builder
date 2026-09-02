import React from "react"
import { Skeleton } from "./skeleton"

export const SidebarSkeleton: React.FC = () => {
  return (
    <div className="animate-in fade-in flex h-full flex-col bg-white duration-300">
      {/* Header Area */}
      <div className="shrink-0 border-b border-gray-100 px-4 py-3">
        <Skeleton className="h-5 w-32 bg-gray-100" />
      </div>

      {/* List/Content Area */}
      <div className="flex-1 space-y-6 overflow-hidden p-4">
        {/* Simulated Section Blocks */}
        {[1, 2, 3, 4, 5].map((i) => (
          <div key={i} className="space-y-2">
            <div className="flex items-center gap-2">
              <Skeleton className="h-4 w-4 rounded bg-gray-100" />
              <Skeleton className="h-4 w-24 bg-gray-100" />
            </div>
            <div className="space-y-2 pl-6">
              <Skeleton className="h-3 w-full bg-gray-50" />
              <Skeleton className="h-3 w-4/5 bg-gray-50" />
            </div>
          </div>
        ))}
      </div>

      {/* Footer Area */}
      <div className="shrink-0 border-t border-gray-100 p-4">
        <Skeleton className="h-9 w-full rounded-lg bg-gray-100" />
      </div>
    </div>
  )
}
