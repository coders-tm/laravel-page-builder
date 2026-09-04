/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
type Listener = (...args: any[]) => void

export class EventBus {
  private listeners = new Map<string, Set<Listener>>()

  /**
   * Subscribe to an event.
   *
   * @returns An unsubscribe function.
   */
  on(event: string, listener: Listener): () => void {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set())
    }
    this.listeners.get(event)!.add(listener)

    return () => {
      this.listeners.get(event)?.delete(listener)
    }
  }

  /**
   * Subscribe to an event for a single invocation.
   */
  once(event: string, listener: Listener): () => void {
    const wrapper = (...args: any[]) => {
      off()
      listener(...args)
    }
    const off = this.on(event, wrapper)
    return off
  }

  /**
   * Emit an event to all subscribers.
   */
  emit(event: string, ...args: any[]): void {
    const set = this.listeners.get(event)
    if (!set) return
    for (const listener of set) {
      try {
        listener(...args)
      } catch (err) {
        console.error(`[EventBus] Error in "${event}" listener:`, err)
      }
    }
  }

  /**
   * Remove all listeners (used during teardown).
   */
  clear(): void {
    this.listeners.clear()
  }

  /**
   * Remove all listeners for a specific event.
   */
  off(event: string): void {
    this.listeners.delete(event)
  }
}
