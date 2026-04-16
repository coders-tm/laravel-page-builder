import React from "react";
import ReactDOM from "react-dom/client";
import { BrowserRouter, Routes, Route } from "react-router-dom";
import App from "./App";
import { createEditor } from "./core/editor";
import { EditorProvider } from "./core/editorContext";
import "./styles.css";

import { setConfig, PageBuilderConfig, EditorConfig } from "./config";
import { registerCoreFields } from "./core/registry/registerCoreFields";
import { FieldRegistry } from "./core/registry/FieldRegistry";
import type { Editor } from "./core/editor/Editor";

let rootInstance: ReactDOM.Root | null = null;
let editorInstance: Editor | null = null;

// Initialize Field Registry early
registerCoreFields();

const PageBuilder = {
  init(
    configParams: Partial<EditorConfig> &
      Partial<PageBuilderConfig> & {
        container?: string | HTMLElement;
        basename?: string;
      } = {},
  ) {
    // Set the internal configuration store
    setConfig(configParams);

    const containerFallback = document.getElementById("root");
    let container: HTMLElement | null = null;

    if (typeof configParams.container === "string") {
      container = document.querySelector(configParams.container);
    } else if (configParams.container instanceof HTMLElement) {
      container = configParams.container;
    } else {
      container = containerFallback;
    }

    if (!container) {
      console.error("PageBuilder: container element not found.");
      return;
    }

    editorInstance = createEditor(configParams);

    if (!rootInstance) {
      rootInstance = ReactDOM.createRoot(container);
    }

    rootInstance.render(
      <React.StrictMode>
        <EditorProvider editor={editorInstance}>
          <BrowserRouter basename={configParams.basename || "/pagebuilder"}>
            <Routes>
              {/* Match any nested path (including slashes) and let the
                                navigation hook derive the slug from the location. */}
              <Route path="/*" element={<App />} />
            </Routes>
          </BrowserRouter>
        </EditorProvider>
      </React.StrictMode>,
    );

    // Signal that the editor is ready
    editorInstance.ready();

    // Return a public API
    return {
      /** Subscribe to editor change events. */
      onChange(callback: (data: any) => void) {
        // Legacy DOM event support
        window.addEventListener("pagebuilder:change", (e: any) =>
          callback(e.detail),
        );
      },

      /** Subscribe to page change events. */
      onPageChange(callback: (data: { slug: string | null }) => void) {
        window.addEventListener("pagebuilder:page-change", (e: any) =>
          callback(e.detail),
        );
      },

      /** Subscribe to exit event. */
      onExit(callback: () => void) {
        window.addEventListener("pagebuilder:exit", () => callback());
      },

      /** Trigger a change event. */
      triggerChange(data: any) {
        window.dispatchEvent(
          new CustomEvent("pagebuilder:change", { detail: data }),
        );
      },

      /** Register a custom field type. */
      registerFieldType(type: string, renderer: any) {
        FieldRegistry.register(type, renderer);
      },

      /** Get the central editor instance with all managers. */
      getEditor(): Editor {
        return editorInstance!;
      },

      /**
       * @deprecated Use getEditor() instead.
       */
      getInstance() {
        return editorInstance;
      },
    };
  },

  createEditor,
};

export default PageBuilder;

// Make PageBuilder globally available strictly so `npm run dev` ES modules
// behave exactly identically to the UMD bundle output.
if (typeof window !== "undefined") {
  (window as any).PageBuilder = PageBuilder;
}
