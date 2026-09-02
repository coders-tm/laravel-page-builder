#!/usr/bin/env node
/**
 * generate-icons.mjs
 *
 * Generates TypeScript icon-data files from installed packages:
 *   - FontAwesome Free  → @fortawesome/free-{solid,regular,brands}-svg-icons
 *   - Material Design   → @material-design-icons/svg/{variant}/*.svg
 *
 * Output:
 *   resources/js/components/settings/fields/icon-data/fa-icons.ts
 *   resources/js/components/settings/fields/icon-data/md-icons.ts
 *
 * Run:  node scripts/generate-icons.mjs
 *       (or via `yarn generate:icons`)
 */

import fs from "fs";
import path from "path";
import { createRequire } from "module";
import { fileURLToPath } from "url";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");
const outDir = path.join(
    root,
    "resources/js/components/settings/fields/icon-data"
);
const require = createRequire(import.meta.url);

fs.mkdirSync(outDir, { recursive: true });

// ─────────────────────────────────────────────────────────────────────────────
// Helper: convert kebab-case / snake_case to Title Case label
// ─────────────────────────────────────────────────────────────────────────────
function toTitleCase(str) {
    return str.replace(/[-_]+/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. FontAwesome Free
// ─────────────────────────────────────────────────────────────────────────────
(function generateFa() {
    const pkgs = [
        {
            name: "@fortawesome/free-solid-svg-icons",
            prefix: "fas",
            group: "Solid",
        },
        {
            name: "@fortawesome/free-regular-svg-icons",
            prefix: "far",
            group: "Regular",
        },
        {
            name: "@fortawesome/free-brands-svg-icons",
            prefix: "fab",
            group: "Brands",
        },
    ];

    const entries = [];
    const seen = new Set();

    for (const { name, prefix, group } of pkgs) {
        let pkg;
        try {
            pkg = require(name);
        } catch (e) {
            console.warn(
                `[generate-icons] Could not load ${name}: ${e.message}`
            );
            continue;
        }

        // Deduplicate by iconName (some icons have alias exports)
        const iconMap = new Map();
        Object.values(pkg).forEach((icon) => {
            if (
                icon &&
                typeof icon === "object" &&
                icon.iconName &&
                icon.prefix === prefix
            ) {
                if (!iconMap.has(icon.iconName)) {
                    iconMap.set(icon.iconName, icon);
                }
            }
        });

        const sorted = Array.from(iconMap.keys()).sort((a, b) => {
            // Push numeric icon names (0-9…) to the end
            const aNum = /^\d/.test(a);
            const bNum = /^\d/.test(b);
            if (aNum && !bNum) return 1;
            if (!aNum && bNum) return -1;
            return a.localeCompare(b);
        });
        sorted.forEach((iconName) => {
            const value = `${prefix} fa-${iconName}`;
            if (seen.has(value)) return;
            seen.add(value);
            entries.push({
                label: toTitleCase(iconName),
                value,
                group,
            });
        });

        console.log(
            `[generate-icons] FA ${group.padEnd(8)} ✓  ${iconMap.size} icons`
        );
    }

    const outPath = path.join(outDir, "fa-icons.ts");
    const lines = [
        "// AUTO-GENERATED — do not edit manually.",
        "// Re-generate with: yarn generate:icons",
        "//",
        "// Source: @fortawesome/free-{solid,regular,brands}-svg-icons",
        `// Total: ${entries.length} entries`,
        "",
        "export type FaIcon = { label: string; value: string; group?: string };",
        "",
        `const FA_ICONS: FaIcon[] = ${JSON.stringify(entries, null, 2)};`,
        "",
        "export default FA_ICONS;",
    ];
    fs.writeFileSync(outPath, lines.join("\n"), "utf8");
    console.log(
        `[generate-icons] FA  ✓  ${
            entries.length
        } total entries → ${path.relative(root, outPath)}`
    );
})();

// ─────────────────────────────────────────────────────────────────────────────
// 2. Material Design Icons
// ─────────────────────────────────────────────────────────────────────────────
(function generateMd() {
    const mdBase = path.join(root, "node_modules/@material-design-icons/svg");

    if (!fs.existsSync(mdBase)) {
        console.warn(
            "[generate-icons] @material-design-icons/svg not found at:\n  " +
                mdBase
        );
        console.warn("  Run: yarn add @material-design-icons/svg --dev");
        return;
    }

    /**
     * Map variant directory → CSS font-family class.
     * We use "filled" as the canonical icon name source; all variants share
     * the same set of icon names.
     */
    const variantMap = {
        filled: "material-icons",
        outlined: "material-icons-outlined",
        round: "material-icons-round",
        sharp: "material-icons-sharp",
        "two-tone": "material-icons-two-tone",
    };

    // Collect icon names from the "filled" variant (canonical)
    const filledDir = path.join(mdBase, "filled");
    const iconNames = fs
        .readdirSync(filledDir)
        .filter((f) => f.endsWith(".svg"))
        .map((f) => f.replace(/\.svg$/, ""))
        .sort();

    /**
     * Build entries: one entry per (icon, variant) combination.
     * The `value` stored is the ligature name (e.g. "home").
     * The `group` is the variant class name so the component can render
     * the correct font-family.
     */
    const entries = [];
    const variantEntries = {}; // variant → count

    Object.entries(variantMap).forEach(([dir, fontClass]) => {
        const variantDir = path.join(mdBase, dir);
        // Only include icons that actually exist in this variant
        const available = new Set(
            fs.existsSync(variantDir)
                ? fs
                      .readdirSync(variantDir)
                      .filter((f) => f.endsWith(".svg"))
                      .map((f) => f.replace(/\.svg$/, ""))
                : []
        );

        let count = 0;
        iconNames.forEach((name) => {
            if (!available.has(name)) return;
            entries.push({
                label: toTitleCase(name),
                value: name,
                group: fontClass,
            });
            count++;
        });

        variantEntries[dir] = count;
    });

    const lines = [
        "// AUTO-GENERATED — do not edit manually.",
        "// Re-generate with: yarn generate:icons",
        "//",
        `// Source: @material-design-icons/svg (${
            entries.length
        } entries across ${Object.keys(variantMap).length} variants)`,
        "",
        "export type MdIcon = { label: string; value: string; group?: string };",
        "",
        `const MD_ICONS: MdIcon[] = ${JSON.stringify(entries, null, 2)};`,
        "",
        "export default MD_ICONS;",
    ];

    const outPath = path.join(outDir, "md-icons.ts");
    fs.writeFileSync(outPath, lines.join("\n"), "utf8");

    const summary = Object.entries(variantEntries)
        .map(([v, c]) => `${v}:${c}`)
        .join(", ");
    console.log(
        `[generate-icons] MD  ✓  ${
            entries.length
        } entries (${summary}) → ${path.relative(root, outPath)}`
    );
})();
