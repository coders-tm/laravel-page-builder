#!/usr/bin/env node
import { readFileSync, writeFileSync } from "fs"
import { resolve, dirname } from "path"
import { execSync } from "child_process"
import { fileURLToPath } from "url"

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)
const rootDir = resolve(__dirname)

const licenseBanner = `/*
 * This file is part of the Laravel Page Builder package.
 *
 * (c) Dipak Sarkar <dipak@coderstm.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
`

function runCommand(command: string): void {
  console.log(`\n> ${command}`)
  execSync(command, { cwd: rootDir, stdio: "inherit" })
}

function incrementVersion(currentVersion: string): string {
  const parts = currentVersion.split(".").map((p) => parseInt(p, 10))
  if (parts.length !== 3 || parts.some(isNaN)) {
    throw new Error(`Invalid version format in package.json: ${currentVersion}`)
  }

  let [major, minor, patch] = parts

  patch += 1
  if (patch > 9) {
    patch = 0
    minor += 1
  }
  if (minor > 9) {
    minor = 0
    major += 1
  }

  return `${major}.${minor}.${patch}`
}

function prependBanner(filePath: string, banner: string): void {
  const content = readFileSync(filePath, "utf-8")
  if (!content.includes("This file is part of the Laravel Page Builder package.")) {
    writeFileSync(filePath, banner + content, "utf-8")
    console.log(`[build] Added license banner to ${filePath}`)
  }
}

function main(): void {
  const pkgPath = resolve(rootDir, "package.json")
  const pkgRaw = readFileSync(pkgPath, "utf-8")
  const pkg = JSON.parse(pkgRaw)

  const oldVersion = pkg.version
  const newVersion = incrementVersion(oldVersion)

  console.log(`[build] Step 1: Incrementing package.json version: ${oldVersion} -> ${newVersion}`)

  pkg.version = newVersion
  writeFileSync(pkgPath, JSON.stringify(pkg, null, 2) + "\n", "utf-8")

  try {
    console.log("\n[build] Step 2: Running generate-icons.mjs...")
    runCommand("node generate-icons.mjs")

    console.log("\n[build] Step 3: Building package...")
    runCommand("npx vite build")

    console.log("\n[build] Step 4: Adding license banners to dist files...")
    prependBanner(resolve(rootDir, "dist/app.js"), licenseBanner)
    prependBanner(resolve(rootDir, "dist/app.umd.js"), licenseBanner)

    console.log("\n[build] Step 5: Committing changes...")
    runCommand("git add -A")
    runCommand(`git commit -m "chore: bump version to v${newVersion}"`)

    console.log("\n[build] Step 6: Creating git tag...")
    runCommand(`git tag v${newVersion}`)

    console.log(`\n[build] Successfully built, committed, and tagged version v${newVersion}!`)
  } catch (error) {
    console.error("\n[build] Build process failed:", error)
    process.exit(1)
  }
}

main()
