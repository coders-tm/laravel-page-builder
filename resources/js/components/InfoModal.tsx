import React from "react"
import { ExternalLink } from "lucide-react"
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog"
import { version } from "../../../package.json"

interface InfoModalProps {
  isOpen: boolean
  onClose: () => void
}

const PACKAGE_NAME = "laravel-page-builder"
const VERSION = version
const DESCRIPTION =
  "A section-based page builder for Laravel using JSON layouts, sections, blocks, and themes."
const AUTHOR = "Dipak Sarkar"
const AUTHOR_EMAIL = "dipak@coderstm.com"
const REPO_URL = "https://github.com/coders-tm/laravel-page-builder"
const LICENSE = "Source-Available Non-Commercial"

export default function InfoModal({ isOpen, onClose }: InfoModalProps) {
  return (
    <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="mx-[15px] max-w-md gap-0 p-0 sm:mx-auto">
        <DialogHeader className="px-6 pt-6 pb-0">
          <div className="flex items-center gap-3">
            <div className="flex h-12 w-12 items-center justify-center overflow-hidden rounded-xl">
              <svg
                width="178"
                height="178"
                viewBox="0 0 178 178"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <rect width="178" height="178" fill="#282E40" />
                <rect
                  x="32"
                  y="30"
                  width="84"
                  height="45"
                  rx="4"
                  stroke="#5C58E2"
                  stroke-width="4"
                  stroke-dasharray="15 7"
                />
                <rect
                  x="32"
                  y="88"
                  width="49"
                  height="50"
                  rx="4"
                  stroke="#C4C4C4"
                  stroke-width="4"
                  stroke-dasharray="15 7"
                />
                <path
                  d="M143 86C146.314 86 149 88.6863 149 92V126.964C148.614 126.805 148.212 126.641 147.793 126.47L132.061 120.038C130.34 119.335 128.929 118.756 127.796 118.409C126.687 118.07 125.504 117.836 124.349 118.158C122.737 118.607 121.41 119.752 120.729 121.28H120.728C120.239 122.376 120.297 123.58 120.47 124.727C120.647 125.899 121.012 127.38 121.455 129.187L124.602 142H99C95.6863 142 93 139.314 93 136V92C93 88.6863 95.6863 86 99 86H143Z"
                  fill="url(#paint0_linear_2001_156)"
                />
                <path
                  d="M147.173 139.249C151.308 137.481 153.375 136.597 153.989 135.365C154.521 134.296 154.511 133.037 153.962 131.976C153.329 130.754 151.247 129.903 147.085 128.202L131.353 121.77C127.841 120.334 126.085 119.616 124.85 119.96C123.776 120.26 122.891 121.023 122.437 122.042C121.915 123.213 122.368 125.055 123.272 128.74L127.28 145.061C128.393 149.594 128.949 151.86 130.088 152.667C131.076 153.366 132.341 153.544 133.483 153.146C134.801 152.686 135.963 150.662 138.287 146.615L140.004 143.625C140.374 142.981 140.559 142.659 140.797 142.384C141.009 142.141 141.251 141.926 141.517 141.744C141.818 141.539 142.159 141.393 142.842 141.101L147.173 139.249Z"
                  fill="#5752D8"
                  stroke="white"
                  stroke-width="3.74215"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <defs>
                  <linearGradient
                    id="paint0_linear_2001_156"
                    x1="121"
                    y1="86"
                    x2="121"
                    y2="142"
                    gradientUnits="userSpaceOnUse"
                  >
                    <stop stop-color="#7571F3" />
                    <stop offset="1" stop-color="#5656DC" />
                  </linearGradient>
                </defs>
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
