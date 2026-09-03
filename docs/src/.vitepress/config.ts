import { defineConfig } from "vitepress"
import sidebar from "./sidebar/index"

export default defineConfig({
  title: "Laravel Page Builder",
  description: "Multi-theme, JSON-driven page builder for Laravel with visual editor",
  base: "/laravel-page-builder/",
  ignoreDeadLinks: true,

  head: [["link", { rel: "icon", type: "image/png", href: "/favicon.png" }]],

  vite: {
    css: {
      postcss: {},
    },
  },

  markdown: {
    headers: {
      level: [0, 0],
    },
  },

  themeConfig: {
    logo: {
      light: "/logo.svg",
      dark: "/logo-dark.svg",
      alt: "Laravel Page Builder",
    },
    siteTitle: false,

    nav: [
      { text: "Sponsor", link: "/sponsor" },
      { text: "About Us", link: "https://coderstm.com" },
    ],

    sidebar: sidebar,

    socialLinks: [{ icon: "github", link: "https://github.com/coders-tm/laravel-page-builder" }],

    footer: {
      message: "Released under the Source-Available Non-Commercial License.",
      copyright: "Copyright © 2024-2026 Dipak Sarkar",
    },

    search: {
      provider: "local",
    },

    editLink: {
      pattern: "https://github.com/coders-tm/laravel-page-builder/edit/main/website/docs/:path",
      text: "Edit this page on GitHub",
    },

    lastUpdated: {
      text: "Last updated",
    },
  },

  lastUpdated: true,
})
