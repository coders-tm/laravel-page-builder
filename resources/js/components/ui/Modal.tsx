/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import React, { useEffect, useRef, useCallback } from "react"
import { X } from "lucide-react"

/**
 * Reusable modal component with focus trap, ESC close,
 * and outside-click close.
 *
 * @param {boolean} isOpen - Whether the modal is visible
 * @param {Function} onClose - Callback when the modal should close
 * @param {string} title - Modal header title
 * @param {React.ReactNode} children - Modal body content
 * @param {React.ReactNode} footer - Optional modal footer
 */
export default function Modal({ isOpen, onClose, title, children, footer }) {
  const overlayRef = useRef(null)
  const modalRef = useRef(null)

  // Close on ESC
  const handleKeyDown = useCallback(
    (e) => {
      if (e.key === "Escape") onClose()
    },
    [onClose],
  )

  useEffect(() => {
    if (!isOpen) return
    document.addEventListener("keydown", handleKeyDown)
    return () => document.removeEventListener("keydown", handleKeyDown)
  }, [isOpen, handleKeyDown])

  // Focus trap
  useEffect(() => {
    if (!isOpen || !modalRef.current) return
    const focusable = modalRef.current.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
    )
    if (focusable.length > 0) focusable[0].focus()
  }, [isOpen])

  if (!isOpen) return null

  // Close on outside click
  const handleOverlayClick = (e) => {
    if (e.target === overlayRef.current) onClose()
  }

  return (
    <div
      ref={overlayRef}
      onClick={handleOverlayClick}
      className="fixed inset-0 z-[200] flex items-center justify-center bg-gray-900/50 p-4 backdrop-blur-sm"
    >
      <div
        ref={modalRef}
        role="dialog"
        aria-modal="true"
        aria-label={title}
        className="flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl"
      >
        {/* Header */}
        <div className="flex shrink-0 items-center justify-between border-b border-gray-100 px-5 py-3.5">
          <h3 className="text-sm font-semibold text-gray-800">{title}</h3>
          <button
            onClick={onClose}
            className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto">{children}</div>

        {/* Footer */}
        {footer && <div className="shrink-0 border-t border-gray-100 px-5 py-3">{footer}</div>}
      </div>
    </div>
  )
}
