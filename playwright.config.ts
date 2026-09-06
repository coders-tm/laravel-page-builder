import { defineConfig } from "@playwright/test"

export default defineConfig({
  testDir: "./videos",
  outputDir: "./videos/test-results",
  timeout: 60_000,
  use: {
    browserName: "chromium",
    viewport: { width: 1920, height: 1080 },
    video: "on",
    trace: "off",
    launchOptions: {
      args: ["--no-sandbox"],
    },
  },
})
