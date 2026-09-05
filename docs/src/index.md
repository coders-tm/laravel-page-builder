---
layout: home
hero:
  name: Laravel Page Builder
  text: Multi-theme, JSON-driven page builder for Laravel
  tagline: Build dynamic pages with layouts, sections, and blocks using a visual editor
  image:
    src: /hero.png
    alt: Laravel Page Builder
  actions:
    - theme: brand
      text: Get Started
      link: /installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/coders-tm/laravel-page-builder

features:
  - icon: 🎨
    title: Blade-Native Rendering
    details: Sections and blocks are regular Blade views with typed PHP objects. No special syntax to learn.
  - icon: 📝
    title: "Schema Directive"
    details: Declare settings, child blocks, and presets directly in Blade templates using a simple array syntax.
  - icon: 🖥️
    title: Visual Editor
    details: React SPA with iframe live preview, drag-and-drop, and inline text editing. Real-time collaboration ready.
  - icon: 📁
    title: JSON-Based Storage
    details: Page data stored as JSON files on disk for fast reads and easy version control integration.
  - icon: 🔄
    title: JSON Templates
    details: Fallback layouts for pages without per-page JSON. Supports variable interpolation and theme overrides.
  - icon: 📱
    title: Per-Page Layouts
    details: Site header and footer are configurable per-page, stored in the page JSON.
  - icon: 🧩
    title: Recursive Block Nesting
    details: Container blocks (rows, columns) can hold child blocks to any depth. Build complex layouts easily.
  - icon: 🎭
    title: Theme Blocks
    details: Register global block types that any section can accept via theme wildcard. Reusable components.
  - icon: ⚡
    title: 21+ Field Types
    details: From basic text inputs to advanced color pickers, icon selectors, and custom types.
  - icon: 📦
    title: Publishable Assets
    details: Config, views, migrations, and frontend assets can be published independently.
  - icon: 🚀
    title: Performance Optimized
    details: Schema caching, Blade publishing, and fragment selection for lightning-fast page loads.
  - icon: 🌐
    title: Multilanguage Support
    details: Locale-specific page JSON, templates, and Blade views with automatic fallback. Editor language selector included.
---

<style>
:root {
  --vp-home-hero-name-color: transparent;
  --vp-home-hero-name-background: -webkit-linear-gradient(120deg, #6366f1 30%, #4f46e5);
}

.vp-hero .VPImage {
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.1);
}
</style>
