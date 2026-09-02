import React from "react"
import { ExternalLink } from "lucide-react"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog"

interface InfoModalProps {
  isOpen: boolean
  onClose: () => void
}

const PACKAGE_NAME = "laravel-page-builder"
const VERSION = "1.0.0"
const DESCRIPTION =
  "A section-based page builder for Laravel using JSON layouts, sections, blocks, and themes."
const AUTHOR = "Dipak Sarkar"
const AUTHOR_EMAIL = "dipak@coderstm.com"
const AUTHOR_URL = "https://dipaksarkar.in"
const REPO_URL = "https://github.com/coders-tm/laravel-page-builder"
const LICENSE = "Source-Available Non-Commercial"

export default function InfoModal({ isOpen, onClose }: InfoModalProps) {
  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="mx-[15px] max-w-md gap-0 p-0 sm:mx-auto">
        <DialogHeader className="px-6 pt-6 pb-0">
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-900 text-white">
              <svg
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="2"
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <rect x="3" y="3" width="7" height="7" />
                <rect x="14" y="3" width="7" height="7" />
                <rect x="3" y="14" width="7" height="7" />
                <rect x="14" y="14" width="7" height="7" />
              </svg>
            </div>
            <div>
              <DialogTitle className="text-lg font-semibold">Laravel Page Builder</DialogTitle>
              <DialogDescription className="mt-0.5 text-xs text-gray-500">
                Version {VERSION}
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <div className="px-6 py-5">
          <p className="text-sm leading-relaxed text-gray-600">{DESCRIPTION}</p>

          <div className="mt-5 space-y-3">
            <InfoRow label="Author" value={AUTHOR} />
            <InfoRow label="Email" value={AUTHOR_EMAIL} href={`mailto:${AUTHOR_EMAIL}`} />
            <InfoRow label="License" value={LICENSE} />
            <InfoRow label="Package" value={PACKAGE_NAME} />
          </div>
        </div>

        <div className="flex items-center justify-between border-t border-gray-100 px-6 py-3.5">
          <span className="text-[11px] text-gray-400">Built with Laravel &amp; React</span>
          <a
            href={REPO_URL}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-1.5 rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-gray-800"
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
              <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
            </svg>
            GitHub
            <ExternalLink className="h-3 w-3 opacity-60" />
          </a>
        </div>
      </DialogContent>
    </Dialog>
  )
}

function InfoRow({ label, value, href }: { label: string; value: string; href?: string }) {
  return (
    <div className="flex items-center justify-between text-sm">
      <span className="text-gray-500">{label}</span>
      {href ? (
        <a
          href={href}
          target="_blank"
          rel="noopener noreferrer"
          className="font-medium text-gray-900 transition-colors hover:text-blue-600"
        >
          {value}
        </a>
      ) : (
        <span className="font-medium text-gray-900">{value}</span>
      )}
    </div>
  )
}
