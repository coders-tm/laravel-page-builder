/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { IframeAdapter } from "@/core/messaging/IframeAdapter"
import type { IMessageBus } from "@/core/messaging/MessageBus"
import type { RefObject } from "react"

/**
 * InteractionManager — class-based iframe interaction helpers.
 */
export class InteractionManager {
  private messageBus: IMessageBus | null = null
  private messageBusRef: RefObject<HTMLIFrameElement> | null = null
  private hoverScrollTimer = 0

  createMessageBus(iframeRef: RefObject<HTMLIFrameElement>): IMessageBus {
    // Recreate the adapter when the ref object changes (eg. StrictMode remounts)
    // so preview messages always target the live iframe instance.
    if (!this.messageBus || this.messageBusRef !== iframeRef) {
      this.messageBus = new IframeAdapter(iframeRef)
      this.messageBusRef = iframeRef
    }
    return this.messageBus
  }

  setMessageBus(bus: IMessageBus): void {
    this.messageBus = bus
  }

  getMessageBus(): IMessageBus | null {
    return this.messageBus
  }

  /**
   * Hover-highlight a section/block and debounce scroll-to-section by 3s.
   */
  hover(sectionId: string | null, blockId: string | null = null): void {
    if (!this.messageBus) return

    this.messageBus.send("hover-section", { sectionId, blockId })

    if (this.hoverScrollTimer) {
      window.clearTimeout(this.hoverScrollTimer)
      this.hoverScrollTimer = 0
    }

    if (sectionId !== null) {
      this.hoverScrollTimer = window.setTimeout(() => {
        this.hoverScrollTimer = 0
        this.messageBus?.send("scroll-to-section", { sectionId })
      }, 3000)
    }
  }

  /**
   * Scroll to and focus a setting field in the sidebar.
   */
  focusSetting(settingPath: string, options?: { openImageModal?: boolean }): void {
    if (!settingPath) return

    const parts = settingPath.split(".")
    const lastId = parts[parts.length - 1]

    const tryFocus = (): boolean => {
      const wrapper =
        document.querySelector(`[data-setting-id="${settingPath}"]`) ||
        document.querySelector(`[data-setting-id="${lastId}"]`)

      if (!wrapper) return false

      wrapper.scrollIntoView({ behavior: "smooth", block: "center" })

      if (options?.openImageModal) {
        const button = wrapper.querySelector("button")
        if (button) {
          ;(button as HTMLElement).click()
        }
      } else {
        const input = wrapper.querySelector('input, [contenteditable="true"], textarea')
        if (input) {
          ;(input as HTMLElement).focus()
        }
      }
      return true
    }

    const delays = [150, 300, 500]
    let attempt = 0

    const scheduleAttempt = () => {
      if (attempt >= delays.length) return
      window.setTimeout(() => {
        if (!tryFocus()) {
          attempt += 1
          scheduleAttempt()
        }
      }, delays[attempt])
    }

    scheduleAttempt()
  }
}
