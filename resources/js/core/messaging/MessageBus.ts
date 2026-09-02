export interface MessagePayload {
  [key: string]: any
}

export interface IMessageBus {
  send(type: string, payload?: MessagePayload): void
  on(type: string, observer: (payload: any) => void): () => void
}
