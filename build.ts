#!/usr/bin/env node
import { readFileSync, writeFileSync } from "fs"
import { resolve, dirname } from "path"
import { execSync } from "child_process"
import { fileURLToPath } from "url"
import { select } from "@inquirer/prompts"

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

function bumpVersion(currentVersion: string, type: "patch" | "minor" | "major" = "patch"): string {
  const parts = currentVersion.split(".").map((p) => parseInt(p, 10))
  if (parts.length !== 3 || parts.some(isNaN)) {
    throw new Error(`Invalid version format in package.json: ${currentVersion}`)
  }

  let [major, minor, patch] = parts

  if (type === "patch") {
    patch += 1
  } else if (type === "minor") {
    minor += 1
    patch = 0
  } else if (type === "major") {
    major += 1
    minor = 0
    patch = 0
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

async function main(): Promise<void> {
  const pkgPath = resolve(rootDir, "package.json")
  const pkgRaw = readFileSync(pkgPath, "utf-8")
  const pkg = JSON.parse(pkgRaw)

  const oldVersion: string = pkg.version

  const args = process.argv.slice(2)
  const isNoVersionFlag = args.includes("--no-bump")
  const isMinorFlag = args.includes("--minor")
  const isMajorFlag = args.includes("--major")

  let selectedOption: "patch" | "minor" | "major" | "skip" = "patch"

  if (isNoVersionFlag) {
    selectedOption = "skip"
  } else if (isMinorFlag) {
    selectedOption = "minor"
  } else if (isMajorFlag) {
    selectedOption = "major"
  } else if (process.stdin.isTTY && args.length === 0) {
    try {
      selectedOption = await select({
        message: "Select version bump option:",
        choices: [
          {
            name: `Patch bump (${oldVersion} -> ${bumpVersion(oldVersion, "patch")})`,
            value: "patch",
          },
          {
            name: `Minor bump (${oldVersion} -> ${bumpVersion(oldVersion, "minor")})`,
            value: "minor",
          },
          {
            name: `Major bump (${oldVersion} -> ${bumpVersion(oldVersion, "major")})`,
            value: "major",
          },
          {
            name: "Skip version bump & tag creation",
            value: "skip",
          },
        ],
      })
    } catch {
      console.log("\n[build] Build cancelled.")
      process.exit(0)
    }
  }

  const shouldBumpVersion = selectedOption !== "skip"
  const shouldCreateTag = selectedOption !== "skip"

  let newVersion = oldVersion

  if (shouldBumpVersion) {
    newVersion = bumpVersion(oldVersion, selectedOption as "patch" | "minor" | "major")
    console.log(
      `\n[build] Step 1: Incrementing package.json version: ${oldVersion} -> ${newVersion}`,
    )
    pkg.version = newVersion
    writeFileSync(pkgPath, JSON.stringify(pkg, null, 2) + "\n", "utf-8")
  } else {
    console.log(
      `\n[build] Step 1: Skipping version increment (keeping current version v${oldVersion})`,
    )
  }

  try {
    console.log("\n[build] Step 2: Running generate-icons.mjs...")
    runCommand("node generate-icons.mjs")

    console.log("\n[build] Step 3: Building package...")
    runCommand("npx vite build")

    console.log("\n[build] Step 4: Adding license banners to dist files...")
    prependBanner(resolve(rootDir, "dist/app.js"), licenseBanner)
    prependBanner(resolve(rootDir, "dist/app.umd.js"), licenseBanner)

    if (shouldBumpVersion || shouldCreateTag) {
      console.log("\n[build] Step 5: Committing changes...")
      runCommand("git add -A")
      const commitMsg = shouldBumpVersion
        ? `chore: bump version to v${newVersion}`
        : `chore: build dist assets for v${newVersion}`
      runCommand(`git commit -m "${commitMsg}"`)
    } else {
      console.log("\n[build] Step 5: Skipping git commit.")
    }

    if (shouldCreateTag) {
      console.log(`\n[build] Step 6: Creating git tag v${newVersion}...`)
      runCommand(`git tag v${newVersion}`)
    } else {
      console.log("\n[build] Step 6: Skipping git tag creation.")
    }

    console.log(
      `\n[build] Successfully built assets!` +
        (shouldBumpVersion
          ? ` Version bumped to v${newVersion}.`
          : ` Version v${oldVersion} unchanged.`) +
        (shouldCreateTag ? ` Git tag created.` : ""),
    )
  } catch (error) {
    console.error("\n[build] Build process failed:", error)
    process.exit(1)
  }
}

main()
