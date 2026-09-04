/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { IMessageBus, MessagePayload } from "./MessageBus"
import React from "react"

export class IframeAdapter implements IMessageBus {
  constructor(private iframeRef: React.RefObject<HTMLIFrameElement>) {}

  send(type: string, payload: MessagePayload = {}) {
    if (!this.iframeRef.current?.contentWindow) return
    this.iframeRef.current.contentWindow.postMessage({ type, ...payload }, "*")
  }

  on(type: string, observer: (payload: any) => void) {
    const handler = (event: MessageEvent) => {
      if (event.data?.type === type) {
        observer(event.data)
      }
    }
    window.addEventListener("message", handler)
    return () => window.removeEventListener("message", handler)
  }
}
