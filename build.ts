#!/usr/bin/env node
import { readFileSync, writeFileSync } from "fs"
import { resolve, dirname } from "path"
import { execSync } from "child_process"
import { fileURLToPath } from "url"

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)
const rootDir = resolve(__dirname)

/**
 * Execute a shell command synchronously and inherit stdout/stderr.
 */
function runCommand(command: string): void {
  console.log(`\n> ${command}`)
  execSync(command, { cwd: rootDir, stdio: "inherit" })
}

/**
 * Increment semver version string.
 */
function incrementVersion(currentVersion: string, bumpType: string = "patch"): string {
  const parts = currentVersion.split(".").map((p) => parseInt(p, 10))
  if (parts.length !== 3 || parts.some(isNaN)) {
    throw new Error(`Invalid version format in package.json: ${currentVersion}`)
  }

  let [major, minor, patch] = parts

  if (bumpType === "major") {
    major += 1
    minor = 0
    patch = 0
  } else if (bumpType === "minor") {
    minor += 1
    patch = 0
  } else if (bumpType === "patch") {
    patch += 1
  } else if (/^\d+\.\d+\.\d+$/.test(bumpType)) {
    return bumpType
  } else {
    throw new Error(`Unknown bump type or invalid version: ${bumpType}`)
  }

  return `${major}.${minor}.${patch}`
}

function main(): void {
  const pkgPath = resolve(rootDir, "package.json")
  const pkgRaw = readFileSync(pkgPath, "utf-8")
  const pkg = JSON.parse(pkgRaw)

  const oldVersion = pkg.version
  const bumpArg = process.argv[2] || "patch"
  const newVersion = incrementVersion(oldVersion, bumpArg)

  console.log(`[build] Step 1: Incrementing package.json version: ${oldVersion} -> ${newVersion}`)

  pkg.version = newVersion
  writeFileSync(pkgPath, JSON.stringify(pkg, null, 2) + "\n", "utf-8")

  try {
    console.log("\n[build] Step 2: Running generate-icons.mjs...")
    runCommand("node generate-icons.mjs")

    console.log("\n[build] Step 3: Building package...")
    runCommand("npm run build")

    console.log(`\n[build] Step 4: Committing build version message: build: v${newVersion}...`)
    runCommand("git add -A")
    runCommand(`git commit -m "build: v${newVersion}"`)

    console.log(`\n[build] Successfully built package and committed version v${newVersion}!`)
  } catch (error) {
    console.error("\n[build] Build process failed:", error)
    process.exit(1)
  }
}

main()
