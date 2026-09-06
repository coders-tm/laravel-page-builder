import { chromium, type Browser, type Page, type Frame, type Locator } from "playwright"
import { resolve } from "path"
import { mkdir } from "fs/promises"

const BASE_URL = "https://pagebuilder.test"
const TYPING_DELAY = 15
const PAUSE_AFTER_FIELD = 200
const PAUSE_AFTER_SECTION = 300
const PAUSE_AFTER_SAVE = 200
const PAUSE_AFTER_ICON = 200

interface LiveTextEdit {
  liveSettingId: string
  newValue: string
}

interface IconEdit {
  settingId: string
  iconName: string
}

interface SectionEdit {
  sectionId: string
  label: string
  edits: LiveTextEdit[]
  icons?: IconEdit[]
}

const SECTIONS: SectionEdit[] = [
  {
    sectionId: "hero_1",
    label: "Hero",
    edits: [
      { liveSettingId: "hero_1.badge_text", newValue: "Ship Pages 10x Faster" },
      {
        liveSettingId: "hero_1.title",
        newValue:
          'Compose Dynamic Landing Pages with <span class="block bg-gradient-to-r from-red-500 via-indigo-400 to-sky-400 bg-clip-text text-transparent">Laravel &amp; Blade</span>',
      },
      {
        liveSettingId: "hero_1.subtitle",
        newValue: "JSON-driven page system for Laravel. Zero boilerplate, zero DB, sub-ms renders.",
      },
    ],
    icons: [{ settingId: "pill_1.icon", iconName: "rocket_launch" }],
  },
  {
    sectionId: "features_1",
    label: "Features",
    edits: [
      { liveSettingId: "features_1.badge", newValue: "Why Developers Love It" },
      { liveSettingId: "features_1.title", newValue: "Built for Developers Who Ship Fast" },
      {
        liveSettingId: "features_1.subtitle",
        newValue: "Strict layer separation, PHP 8.2+ typing, pure Blade execution.",
      },
    ],
  },
  {
    sectionId: "cta_banner_1",
    label: "CTA Banner",
    edits: [
      { liveSettingId: "cta_banner_1.badge", newValue: "Ready to Build?" },
      { liveSettingId: "cta_banner_1.title", newValue: "Start Building in 60 Seconds" },
      {
        liveSettingId: "cta_banner_1.description",
        newValue: "One command. Zero config. Pure Blade.",
      },
      { liveSettingId: "cta_banner_1.button_label", newValue: "Get Started Free" },
      { liveSettingId: "cta_banner_1.secondary_label", newValue: "View Source Code" },
    ],
  },
]

async function getPreviewFrame(page: Page): Promise<Frame> {
  const frames = page.frames()
  const previewFrame = frames.find(
    (f) => f.url().includes("?pb-editor=1") || f.name() === "pb-preview-iframe",
  )
  if (!previewFrame) {
    throw new Error("Preview iframe not found")
  }
  return previewFrame
}

async function scrollToElement(frame: Frame, selector: string): Promise<void> {
  await frame.evaluate((sel) => {
    const el = document.querySelector(sel)
    if (el) {
      el.scrollIntoView({ behavior: "smooth", block: "center" })
    }
  }, selector)
}

function getSafePartialHTML(fullHTML: string, charIndex: number): string {
  if (!/<[a-z][\s\S]*>/i.test(fullHTML)) {
    return fullHTML.slice(0, charIndex)
  }

  // If charIndex falls inside an unclosed HTML tag <...>, advance charIndex past the closing '>'
  let effectiveIndex = charIndex
  const lastOpenAngle = fullHTML.lastIndexOf("<", effectiveIndex - 1)
  const lastCloseAngle = fullHTML.lastIndexOf(">", effectiveIndex - 1)

  if (lastOpenAngle > lastCloseAngle) {
    const tagEnd = fullHTML.indexOf(">", lastOpenAngle)
    if (tagEnd !== -1) {
      effectiveIndex = tagEnd + 1
    }
  }

  let partial = fullHTML.slice(0, effectiveIndex)

  // Auto-close any unclosed HTML tags in the partial string
  const openTags: string[] = []
  const tagRegex = /<\/?([a-z1-6]+)[^>]*>/gi
  let match: RegExpExecArray | null

  while ((match = tagRegex.exec(partial)) !== null) {
    const fullTag = match[0]
    const tagName = match[1].toLowerCase()
    const isClosing = fullTag.startsWith("</")
    const isSelfClosing =
      fullTag.endsWith("/>") || ["img", "br", "hr", "input", "meta"].includes(tagName)

    if (isSelfClosing) continue

    if (isClosing) {
      if (openTags.length > 0 && openTags[openTags.length - 1] === tagName) {
        openTags.pop()
      }
    } else {
      openTags.push(tagName)
    }
  }

  for (let i = openTags.length - 1; i >= 0; i--) {
    partial += `</${openTags[i]}>`
  }

  return partial
}

// ── Visual Mouse Cursor Helpers for Video ─────────────────────────────────────
async function injectCursorOverlay(page: Page): Promise<void> {
  await page.evaluate(() => {
    if (document.getElementById("playwright-mouse-pointer")) return

    const cursor = document.createElement("div")
    cursor.id = "playwright-mouse-pointer"
    cursor.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 40px;
      height: 40px;
      z-index: 99999999;
      pointer-events: none;
      transform: translate3d(100px, 100px, 0);
      transition: transform 0.45s cubic-bezier(0.2, 0.9, 0.3, 1);
    `

    cursor.innerHTML = `
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" style="filter: drop-shadow(0 3px 8px rgba(0,0,0,0.5));">
        <path d="M5.5 3.5L18.5 11.5L12 13.5L9 19.5L5.5 3.5Z" fill="#1b1e24ff" stroke="#FFFFFF" stroke-width="1.8" stroke-linejoin="round"/>
      </svg>
      <div id="playwright-mouse-ripple" style="
        position: absolute;
        top: -6px;
        left: -6px;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        border: 2.5px solid #3579d9ff;
        background: rgba(59, 130, 246, 0.3);
        transform: scale(0);
        opacity: 0;
        pointer-events: none;
        transition: transform 0.35s ease-out, opacity 0.35s ease-out;
      "></div>
    `

    document.body.appendChild(cursor)
  })
}

async function moveCursorTo(page: Page, x: number, y: number): Promise<void> {
  await injectCursorOverlay(page)
  await page.evaluate(
    ({ targetX, targetY }) => {
      const cursor = document.getElementById("playwright-mouse-pointer")
      if (cursor) {
        cursor.style.transform = `translate3d(${targetX}px, ${targetY}px, 0)`
      }
    },
    { targetX: x, targetY: y },
  )

  await page.mouse.move(x, y, { steps: 6 })
  await page.waitForTimeout(200)
}

async function animateClick(page: Page): Promise<void> {
  await page.evaluate(() => {
    const ripple = document.getElementById("playwright-mouse-ripple")
    if (ripple) {
      ripple.style.transition = "none"
      ripple.style.transform = "scale(0.2)"
      ripple.style.opacity = "1"
      void ripple.offsetWidth
      ripple.style.transition = "transform 0.3s ease-out, opacity 0.3s ease-out"
      ripple.style.transform = "scale(2.2)"
      ripple.style.opacity = "0"
    }
  })
  await page.waitForTimeout(100)
}

async function clickElementWithCursor(page: Page, locator: Locator): Promise<void> {
  try {
    const box = await locator.boundingBox()
    if (box) {
      const x = box.x + box.width / 2
      const y = box.y + box.height / 2
      await moveCursorTo(page, x, y)
      await animateClick(page)
    }
    await locator.click()
  } catch (err) {
    await locator.click().catch(() => {})
  }
}

async function clickFrameElementWithCursor(
  page: Page,
  frame: Frame,
  selector: string,
  useEdge: boolean = true,
): Promise<void> {
  try {
    const iframeLocator = page.locator("#pb-preview-iframe")
    const iframeBox = await iframeLocator.boundingBox()
    const elementLocator = frame.locator(selector).first()
    const elementBox = await elementLocator.boundingBox()

    if (iframeBox && elementBox) {
      // Calculate top-left edge offset (24px inset from left, 18px inset from top)
      const offsetX = useEdge
        ? Math.min(24, Math.max(8, elementBox.width * 0.1))
        : elementBox.width / 2
      const offsetY = useEdge
        ? Math.min(18, Math.max(8, elementBox.height * 0.2))
        : elementBox.height / 2

      const x = iframeBox.x + elementBox.x + offsetX
      const y = iframeBox.y + elementBox.y + offsetY

      await moveCursorTo(page, x, y)
      await animateClick(page)

      await elementLocator
        .click({ position: useEdge ? { x: offsetX, y: offsetY } : undefined })
        .catch(() => {})
      return
    }
    await elementLocator.click().catch(() => {})
  } catch (err) {
    const elementLocator = frame.locator(selector).first()
    await elementLocator.click().catch(() => {})
  }
}

async function typeInLiveTextSetting(
  page: Page,
  frame: Frame,
  liveSettingId: string,
  newValue: string,
): Promise<void> {
  const selector = `[data-live-text-setting="${liveSettingId}"]`
  const element = frame.locator(selector).first()

  // Step 1: Scroll to and click element in iframe to select its section/block
  if (await element.isVisible({ timeout: 2000 }).catch(() => false)) {
    await scrollToElement(frame, selector)
    await frame.waitForTimeout(200)
    await clickFrameElementWithCursor(page, frame, selector)
    await frame.waitForTimeout(200)
  }

  // Extract setting key (e.g. 'hero_1.badge_text' -> 'badge_text' or 'hero_1.badge_text')
  const settingKey = liveSettingId.includes(".") ? liveSettingId.split(".").pop()! : liveSettingId

  // Step 2: Look for sidebar input / textarea element
  const sidebarSelectors = [
    `div[data-setting-id="${liveSettingId}"] input`,
    `div[data-setting-id="${liveSettingId}"] textarea`,
    `div[data-setting-id="${settingKey}"] input`,
    `div[data-setting-id="${settingKey}"] textarea`,
    `div[data-setting-id$="${settingKey}"] input`,
    `div[data-setting-id$="${settingKey}"] textarea`,
  ]

  const sidebarField = page.locator(sidebarSelectors.join(", ")).first()
  const isSidebarVisible = await sidebarField.isVisible({ timeout: 2000 }).catch(() => false)
  const isHTML = /<[a-z][\s\S]*>/i.test(newValue)

  if (isSidebarVisible) {
    await sidebarField.scrollIntoViewIfNeeded().catch(() => {})
    await clickElementWithCursor(page, sidebarField)
    await page.waitForTimeout(100)

    // Select all text in sidebar field using Playwright fill or select + type
    await sidebarField.focus()
    await page.keyboard.press("Meta+a").catch(() => {})
    await page.keyboard.press("Control+a").catch(() => {})
    await sidebarField.fill("")
    await page.waitForTimeout(100)

    const htmlTagIndex = newValue.search(/<[a-z]/i)

    if (htmlTagIndex !== -1) {
      // Phase 1: Type plain text prefix character-by-character
      const plainPrefix = newValue.slice(0, htmlTagIndex)
      for (let i = 0; i < plainPrefix.length; i++) {
        await sidebarField.type(plainPrefix[i], { delay: TYPING_DELAY })
      }

      await page.waitForTimeout(200)

      // Phase 2: When HTML tag position is reached, paste the rest all at once
      await sidebarField.fill(newValue)

      // Sync full HTML to preview iframe
      await page.evaluate(
        ({ path, val }) => {
          const iframe = document.getElementById("pb-preview-iframe") as HTMLIFrameElement
          if (iframe && iframe.contentWindow) {
            iframe.contentWindow.postMessage(
              {
                type: "update-live-text",
                path: path,
                value: val,
              },
              "*",
            )
          }
        },
        { path: liveSettingId, val: newValue },
      )
    } else {
      // Plain text: Type all characters one-by-one
      for (let i = 0; i < newValue.length; i++) {
        await sidebarField.type(newValue[i], { delay: TYPING_DELAY })
      }
    }

    await page.waitForTimeout(PAUSE_AFTER_FIELD)
    console.log(`  Typed in sidebar for "${liveSettingId}": "${newValue}"`)
  } else {
    // Fallback: If sidebar input is not visible, update preview directly
    console.log(`  Sidebar field for "${liveSettingId}" not found, updating preview directly...`)
    const htmlTagIndex = newValue.search(/<[a-z]/i)

    if (htmlTagIndex !== -1) {
      const plainPrefix = newValue.slice(0, htmlTagIndex)
      for (let i = 0; i < plainPrefix.length; i++) {
        const currentVal = plainPrefix.slice(0, i + 1)
        await page.evaluate(
          ({ path, val }) => {
            const iframe = document.getElementById("pb-preview-iframe") as HTMLIFrameElement
            if (iframe && iframe.contentWindow) {
              iframe.contentWindow.postMessage(
                {
                  type: "update-live-text",
                  path: path,
                  value: val,
                },
                "*",
              )
            }
          },
          { path: liveSettingId, val: currentVal },
        )

        await page.waitForTimeout(TYPING_DELAY)
      }

      await page.waitForTimeout(200)

      // Paste full HTML string into preview iframe
      await page.evaluate(
        ({ path, val }) => {
          const iframe = document.getElementById("pb-preview-iframe") as HTMLIFrameElement
          if (iframe && iframe.contentWindow) {
            iframe.contentWindow.postMessage(
              {
                type: "update-live-text",
                path: path,
                value: val,
              },
              "*",
            )
          }
        },
        { path: liveSettingId, val: newValue },
      )
    } else {
      for (let i = 0; i < newValue.length; i++) {
        const currentVal = newValue.slice(0, i + 1)
        await page.evaluate(
          ({ path, val }) => {
            const iframe = document.getElementById("pb-preview-iframe") as HTMLIFrameElement
            if (iframe && iframe.contentWindow) {
              iframe.contentWindow.postMessage(
                {
                  type: "update-live-text",
                  path: path,
                  value: val,
                },
                "*",
              )
            }
          },
          { path: liveSettingId, val: currentVal },
        )

        await page.waitForTimeout(TYPING_DELAY)
      }
    }

    await page.waitForTimeout(PAUSE_AFTER_FIELD)
    console.log(`  Typed in preview for "${liveSettingId}": "${newValue}"`)
  }
}

async function changeIcon(
  page: Page,
  frame: Frame,
  settingId: string,
  iconName: string,
): Promise<void> {
  const blockId = settingId.includes(".") ? settingId.split(".")[0] : settingId
  const settingKey = settingId.includes(".") ? settingId.split(".").pop()! : settingId

  // Step 1: Click the block element in the iframe to select it in the editor sidebar
  const blockSelector = `[data-block-id="${blockId}"], [data-live-text-setting="${settingId}"]`
  const blockElement = frame.locator(blockSelector).first()
  if (await blockElement.isVisible({ timeout: 2000 }).catch(() => false)) {
    await scrollToElement(frame, blockSelector)
    await frame.waitForTimeout(200)
    await clickFrameElementWithCursor(page, frame, blockSelector)
    await page.waitForTimeout(400)
  }

  // Step 2: Find the icon picker button in the sidebar for this block
  const iconButtonSelectors = [
    `div[data-setting-id="${settingId}"] button[aria-haspopup="dialog"]`,
    `div[data-setting-id="${settingKey}"] button[aria-haspopup="dialog"]`,
    `div[data-setting-id$="${settingKey}"] button[aria-haspopup="dialog"]`,
    `button:has(span.material-icons)`,
  ]

  let iconButton: Locator | null = null
  for (const sel of iconButtonSelectors) {
    const btn = page.locator(sel).first()
    if (await btn.isVisible({ timeout: 1500 }).catch(() => false)) {
      iconButton = btn
      break
    }
  }

  if (!iconButton) {
    console.log(`  Could not find icon button for ${settingId}`)
    return
  }

  await clickElementWithCursor(page, iconButton)
  await page.waitForTimeout(PAUSE_AFTER_ICON)

  // Wait for icon picker modal to open
  const modal = page.locator('[role="dialog"]').first()
  await modal.waitFor({ state: "visible", timeout: 5000 })

  // Find search input in modal and type icon name
  const searchInput = modal
    .locator('input[type="text"], input[placeholder*="Search"], input[placeholder*="search"]')
    .first()
  if (await searchInput.isVisible({ timeout: 2000 }).catch(() => false)) {
    await clickElementWithCursor(page, searchInput)
    await searchInput.fill(iconName)
    await page.waitForTimeout(500)
  }

  // Click on the first icon result
  const iconResult = modal
    .locator(`button:has-text("${iconName}"), [data-icon="${iconName}"]`)
    .first()
  if (await iconResult.isVisible({ timeout: 2000 }).catch(() => false)) {
    await clickElementWithCursor(page, iconResult)
  } else {
    // Try clicking any icon button in the grid
    const anyIcon = modal
      .locator('button[class*="icon"], .icon-grid button, [role="gridcell"]')
      .first()
    if (await anyIcon.isVisible({ timeout: 2000 }).catch(() => false)) {
      await clickElementWithCursor(page, anyIcon)
    }
  }

  await page.waitForTimeout(PAUSE_AFTER_ICON)

  // Close modal if still open (click outside or close button)
  const closeButton = modal.locator('button[aria-label="Close"], button:has(svg.lucide-x)').first()
  if (await closeButton.isVisible({ timeout: 1000 }).catch(() => false)) {
    await clickElementWithCursor(page, closeButton)
  }

  await page.waitForTimeout(300)
  console.log(`  Changed icon for "${settingId}" to "${iconName}"`)
}

async function savePage(page: Page): Promise<void> {
  const saveBtn = page.locator('button:has-text("Save"):not(:has-text("Saving"))').first()
  await saveBtn.waitFor({ state: "visible", timeout: 5000 })
  await clickElementWithCursor(page, saveBtn)
  console.log("Clicked Save")

  await page.waitForTimeout(300)

  const savingBtn = page.locator('button:has-text("Saving")')
  const isSaving = await savingBtn.count()
  if (isSaving > 0) {
    await savingBtn.waitFor({ state: "hidden", timeout: 10000 })
  }
  await page.waitForTimeout(PAUSE_AFTER_SAVE)
  console.log("Save complete")
}

async function debugPage(page: Page): Promise<void> {
  console.log("\n--- DEBUG INFO ---")
  console.log("Current URL:", page.url())

  const sectionRows = await page.locator("div[data-section-id]").count()
  console.log("Section rows in sidebar:", sectionRows)

  const frames = page.frames()
  const previewFrame = frames.find(
    (f) => f.url().includes("?pb-editor=1") || f.name() === "pb-preview-iframe",
  )
  if (previewFrame) {
    const liveSettings = await previewFrame.locator("[data-live-text-setting]").count()
    console.log("Live text settings in preview:", liveSettings)

    const settings = await previewFrame
      .locator("[data-live-text-setting]")
      .evaluateAll((els) => els.slice(0, 15).map((el) => el.getAttribute("data-live-text-setting")))
    console.log("First 15 live settings:", settings)
  } else {
    console.log("Preview iframe not found")
  }

  console.log("--- END DEBUG ---\n")
}

async function main(): Promise<void> {
  const outputDir = resolve(process.cwd(), "videos")
  await mkdir(outputDir, { recursive: true })

  console.log("Launching Chromium (visible)...")
  const browser: Browser = await chromium.launch({
    headless: false,
    args: ["--no-sandbox", "--start-maximized"],
  })

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: {
      dir: outputDir,
      size: { width: 1920, height: 1080 },
    },
  })

  const page = await context.newPage()

  page.on("console", (msg) => {
    if (msg.type() === "error") {
      console.log("Browser error:", msg.text())
    }
  })

  // Load the editor
  console.log(`Navigating to ${BASE_URL}/?editor=true...`)
  await page.goto(`${BASE_URL}/?editor=true`, { waitUntil: "networkidle" })

  await page.waitForSelector("div[data-section-id]", { timeout: 15000 })
  await page.waitForTimeout(1000)
  console.log("Editor loaded")

  await page.waitForSelector("#pb-preview-iframe", { timeout: 10000 })
  await page.waitForTimeout(2000)

  await injectCursorOverlay(page)
  await debugPage(page)

  const previewFrame = await getPreviewFrame(page)

  // Click on hero section in sidebar first
  const heroSection = page.locator('div[data-section-id="hero_1"]').first()
  if (await heroSection.isVisible()) {
    await clickElementWithCursor(page, heroSection)
    await page.waitForTimeout(500)
  }

  // Edit sections
  for (let i = 0; i < SECTIONS.length; i++) {
    const section = SECTIONS[i]
    console.log(`\n[${i + 1}/${SECTIONS.length}] Editing section: ${section.label}`)

    // Click section in sidebar to open its settings
    const sectionRow = page.locator(`div[data-section-id="${section.sectionId}"]`).first()
    if (await sectionRow.isVisible()) {
      await clickElementWithCursor(page, sectionRow)
      await page.waitForTimeout(500)
    }

    // Edit each live text setting (in preview iframe)
    for (const edit of section.edits) {
      try {
        await typeInLiveTextSetting(page, previewFrame, edit.liveSettingId, edit.newValue)
      } catch (err) {
        console.error(`  Error typing in "${edit.liveSettingId}":`, err)
        // Try to recover by clicking again
        const element = previewFrame
          .locator(`[data-live-text-setting="${edit.liveSettingId}"]`)
          .first()
        await element.click({ clickCount: 3 }).catch(() => {})
        await page.waitForTimeout(200)
        await typeInLiveTextSetting(page, previewFrame, edit.liveSettingId, edit.newValue)
      }
    }

    // Change icons (via settings panel)
    if (section.icons && section.icons.length > 0) {
      for (const iconEdit of section.icons) {
        try {
          await changeIcon(page, previewFrame, iconEdit.settingId, iconEdit.iconName)
        } catch (err) {
          console.error(`  Error changing icon "${iconEdit.settingId}":`, err)
        }
      }
    }

    console.log(`  Section "${section.label}" complete`)
    await page.waitForTimeout(PAUSE_AFTER_SECTION)
  }

  console.log("\nSaving page...")
  await savePage(page)

  // Step 1: Immediately visit BASE_URL after save (no delay)
  console.log(`\nVisiting published page: ${BASE_URL}...`)
  await page.goto(BASE_URL, { waitUntil: "domcontentloaded" })
  await injectCursorOverlay(page)

  // Step 2: Smooth scroll to bottom
  console.log("Scrolling smoothly to bottom of page...")
  await page.evaluate(async () => {
    await new Promise<void>((resolve) => {
      const step = 14
      const timer = setInterval(() => {
        const scrollHeight = document.body.scrollHeight
        window.scrollBy(0, step)

        if (window.innerHeight + window.scrollY >= scrollHeight - 15) {
          clearInterval(timer)
          resolve()
        }
      }, 16)
    })
  })

  await page.waitForTimeout(400)

  // Step 3: Navigate directly to GitHub repository on the same page
  const githubRepoUrl = "https://github.com/coders-tm/laravel-page-builder"
  console.log(`\nNavigating to GitHub repository: ${githubRepoUrl}...`)
  await page.goto(githubRepoUrl, { waitUntil: "domcontentloaded" })
  await page.waitForTimeout(1500)
  await injectCursorOverlay(page)

  // Step 4: Move cursor pointer and click Star button on GitHub repo page
  console.log("Looking for GitHub Star button...")
  const starSelectors = [
    'button[data-testid="star-button"]',
    'button[aria-label*="Star"]',
    'a[aria-label*="Star"]',
    'button:has-text("Star")',
    '#repository-details-container button:has-text("Star")',
    '[aria-label*="star this repository"]',
  ]

  let starBtn: Locator | null = null
  for (const sel of starSelectors) {
    const btn = page.locator(sel).first()
    if (await btn.isVisible({ timeout: 2000 }).catch(() => false)) {
      starBtn = btn
      break
    }
  }

  if (starBtn) {
    console.log("Clicking GitHub Star button...")
    await starBtn.scrollIntoViewIfNeeded().catch(() => {})
    await page.waitForTimeout(400)
    await clickElementWithCursor(page, starBtn)
    await page.waitForTimeout(2000)
  } else {
    console.log("Star button processed, waiting 2s...")
    await page.waitForTimeout(2000)
  }

  const videoPath = await page.video()?.path()
  await context.close()
  await browser.close()

  console.log("\nDone!")
  if (videoPath) {
    console.log(`Video saved to: ${videoPath}`)
  }
}

main().catch((err) => {
  console.error("Error:", err)
  process.exit(1)
})
