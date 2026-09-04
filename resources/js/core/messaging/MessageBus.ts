/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
export interface MessagePayload {
  [key: string]: any
}

export interface IMessageBus {
  send(type: string, payload?: MessagePayload): void
  on(type: string, observer: (payload: any) => void): () => void
}
