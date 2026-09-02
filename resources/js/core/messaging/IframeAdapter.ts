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
