import { EDITOR_CSS, EDITOR_JS } from "./EditorPreviewRuntime"

export function injectEditorScript(contentDocument: Document, contentWindow: Window) {
  if (!contentDocument || !contentWindow) return

  // Check if already injected
  if ((contentWindow as any).__pbEditorInjected) return

  // 1. Inject CSS
  const styleEl = contentDocument.createElement("style")
  styleEl.id = "pb-editor-injected-styles"
  styleEl.innerHTML = EDITOR_CSS
  contentDocument.head.appendChild(styleEl)

  // 2. Inject JS
  const scriptEl = contentDocument.createElement("script")
  scriptEl.id = "pb-editor-injected-script"
  scriptEl.innerHTML = EDITOR_JS
  contentDocument.body.appendChild(scriptEl)
}
