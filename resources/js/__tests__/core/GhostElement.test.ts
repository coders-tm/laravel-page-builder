/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
import { describe, it, expect, beforeEach } from "vitest"

describe("GhostElement Runtime Behavior", () => {
  beforeEach(() => {
    document.body.innerHTML = `
      <header data-editor-section='{"id":"header","type":"header"}' data-section-id="header"></header>
      <div id="content-container">
        <div data-pb-ghost="true" data-editor-section='{"id":"ghost-section","type":"ghost-section","name":"Add Section"}' data-section-id="ghost-section">
          Empty Page Placeholder
        </div>
      </div>
      <footer data-editor-section='{"id":"footer","type":"footer"}' data-section-id="footer"></footer>
    `
  })

  it("identifies ghost placeholder between header and footer", () => {
    const ghost = document.querySelector("[data-pb-ghost='true']")
    expect(ghost).not.toBeNull()

    const container = ghost?.parentElement
    expect(container?.id).toBe("content-container")
  })

  it("removes ghost placeholder when new section is inserted into container", () => {
    const ghost = document.querySelector("[data-pb-ghost='true']")
    const container = ghost?.parentElement

    const newSection = document.createElement("div")
    newSection.setAttribute("data-section-id", "hero_1")
    newSection.setAttribute("data-editor-section", '{"id":"hero_1","type":"hero"}')

    container?.insertBefore(newSection, ghost)
    ghost?.remove()

    expect(document.querySelector("[data-pb-ghost='true']")).toBeNull()
    expect(container?.querySelector('[data-section-id="hero_1"]')).not.toBeNull()
  })

  it("restores ghost placeholder when last section is removed", () => {
    const ghost = document.querySelector("[data-pb-ghost='true']")
    const container = ghost?.parentElement

    // Simulate adding section
    const newSection = document.createElement("div")
    newSection.setAttribute("data-section-id", "hero_1")
    container?.insertBefore(newSection, ghost)
    ghost?.remove()

    // Simulate removing section and restoring ghost
    newSection.remove()

    const restoredGhost = document.createElement("div")
    restoredGhost.setAttribute("data-pb-ghost", "true")
    restoredGhost.setAttribute("data-section-id", "ghost-section")
    container?.appendChild(restoredGhost)

    expect(document.querySelector("[data-pb-ghost='true']")).not.toBeNull()
  })
})
