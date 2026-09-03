import type { DefaultTheme } from "vitepress"

const sidebar: DefaultTheme.SidebarItem[] = [
  {
    text: "Getting Started",
    items: [
      { text: "Installation", link: "/installation" },
      { text: "Configuration", link: "/configuration" },
    ],
  },
  {
    text: "Core Concepts",
    items: [
      { text: "Architecture", link: "/concepts/architecture" },
      { text: "Sections", link: "/concepts/sections" },
      { text: "Blocks", link: "/concepts/blocks" },
      { text: "Layouts", link: "/concepts/layouts" },
      { text: "Themes", link: "/concepts/themes" },
      { text: "Templates", link: "/concepts/templates" },
    ],
  },
  {
    text: "Reference",
    items: [
      { text: "Schema Reference", link: "/reference/schema" },
      { text: "Field Types", link: "/reference/field-types" },
      { text: "Blade Directives", link: "/reference/blade-directives" },
      { text: "API Reference", link: "/reference/api" },
    ],
  },
]

export default sidebar
