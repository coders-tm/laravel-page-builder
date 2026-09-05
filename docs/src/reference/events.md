---
title: Editor Events & Customization
---

# Editor Events & Customization

Laravel Page Builder includes an **event-driven architecture** via an internal `EventBus`. Every user interaction, data mutation, selection change, and preview update dispatches strongly typed events. You can subscribe to these events to customize editor behavior, integrate third-party tools, trigger analytics, or communicate with an embedding application (such as an iframe parent).

---

## Subscribing to Events

When you initialize the page builder using `PageBuilder.init()`, you can access the underlying `Editor` instance using `editor.getEditor()` or listen directly using top-level convenience helpers.

### Using `editor.on()`

```js
const instance = PageBuilder.init({ container: "#editor" })
const editor = instance.getEditor()

// Subscribe to section addition
const unsubscribe = editor.on("section:added", ({ sectionId, type }) => {
  console.log(`Section ${type} added with ID ${sectionId}`)
})

// To unsubscribe later:
unsubscribe()
```

### Using `editor.once()`

Subscribe to an event for a single invocation:

```js
editor.once("editor:ready", () => {
  console.log("Editor is ready!")
})
```

### Emitting Custom Events

You can also emit standard or custom events using `editor.emit()`:

```js
editor.emit("custom:plugin-action", { status: "active" })
```

---

## Event Catalog

Below is a complete reference of events emitted by the page builder:

### 1. Section Events

| Event Name                 | Payload Structure                                    | Trigger Description                                 |
| :------------------------- | :--------------------------------------------------- | :-------------------------------------------------- |
| `section:added`            | `{ sectionId: string, type: string }`                | Dispatched when a new section is added to the page. |
| `section:removed`          | `{ sectionId: string }`                              | Dispatched when a section is deleted.               |
| `section:duplicated`       | `{ sectionId: string, newId: string }`               | Dispatched when a section is cloned.                |
| `section:reordered`        | `{ order: string[] }`                                | Dispatched when sections are reordered.             |
| `section:settings-changed` | `{ sectionId: string, values: Record<string, any> }` | Dispatched when section setting values change.      |
| `section:toggled`          | `{ sectionId: string, disabled: boolean }`           | Dispatched when section visibility is toggled.      |
| `section:renamed`          | `{ sectionId: string, name: string }`                | Dispatched when a section display name is updated.  |

### 2. Block Events

| Event Name               | Payload Structure                                                                                       | Trigger Description                                        |
| :----------------------- | :------------------------------------------------------------------------------------------------------ | :--------------------------------------------------------- |
| `block:added`            | `{ sectionId: string, blockId: string, type: string, parentPath: string[] }`                            | Dispatched when a new block is added.                      |
| `block:removed`          | `{ sectionId: string, blockId: string, parentPath: string[] }`                                          | Dispatched when a block is deleted.                        |
| `block:duplicated`       | `{ sectionId: string, blockId: string, newId: string, parentPath: string[] }`                           | Dispatched when a block is cloned.                         |
| `block:reordered`        | `{ sectionId: string, order: string[], parentPath: string[] }`                                          | Dispatched when blocks are reordered.                      |
| `block:settings-changed` | `{ sectionId: string, blockId: string, values: Record<string, any>, parentPath: string[] }`             | Dispatched when block settings change.                     |
| `block:toggled`          | `{ sectionId: string, blockId: string, disabled: boolean, parentPath: string[] }`                       | Dispatched when block visibility is toggled.               |
| `block:renamed`          | `{ sectionId: string, blockId: string, name: string, parentPath: string[] }`                            | Dispatched when a block is renamed.                        |
| `block:moved`            | `{ fromSectionId: string, toSectionId: string, blockId: string, fromPath: string[], toPath: string[] }` | Dispatched when a block is moved between sections/parents. |

### 3. Selection Events

| Event Name                  | Payload Structure                                    | Trigger Description                        |
| :-------------------------- | :--------------------------------------------------- | :----------------------------------------- |
| `selection:section-changed` | `{ sectionId: string \| null }`                      | Dispatched when section selection changes. |
| `selection:block-changed`   | `{ sectionId: string \| null, blockPath: string[] }` | Dispatched when block selection changes.   |
| `selection:cleared`         | `{}`                                                 | Dispatched when all selection is cleared.  |

### 4. Page & Navigation Events

| Event Name           | Payload Structure                                                                                                                                       | Trigger Description                                   |
| :------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------ | :---------------------------------------------------- |
| `page:loaded`        | `{ slug: string }`                                                                                                                                      | Dispatched when page data has finished loading.       |
| `page:saved`         | `{ slug: string }`                                                                                                                                      | Dispatched when page data is successfully saved.      |
| `page:changed`       | `{ slug: string }`                                                                                                                                      | Dispatched when switching active pages in the editor. |
| `page:meta-updated`  | `{ meta: Record<string, any> }`                                                                                                                         | Dispatched when SEO/meta information changes.         |
| `navigation:changed` | `{ slug?: string, device: string, selectedSection: string \| null, selectedBlock: string \| null, parentBlockId: string \| null, blockPath: string[] }` | Dispatched on route or navigation state change.       |

### 5. Theme & Layout Events

| Event Name                   | Payload Structure                                     | Trigger Description                                                         |
| :--------------------------- | :---------------------------------------------------- | :-------------------------------------------------------------------------- |
| `theme:setting-changed`      | `{ key: string, value: any, cssVar: string \| null }` | Dispatched when a global theme setting changes.                             |
| `layout:device-changed`      | `{ device: string }`                                  | Dispatched when switching preview viewport (`desktop`, `tablet`, `mobile`). |
| `layout:inspector-toggled`   | `{ enabled: boolean }`                                | Dispatched when opening/closing the settings inspector.                     |
| `layout:sidebar-tab-changed` | `{ tab: string }`                                     | Dispatched when switching sidebar tabs (`sections`, `theme`, `pages`).      |

### 6. History & Lifecycle Events

| Event Name         | Payload Structure | Trigger Description                                 |
| :----------------- | :---------------- | :-------------------------------------------------- |
| `history:undo`     | `{}`              | Dispatched when an undo action is performed.        |
| `history:redo`     | `{}`              | Dispatched when a redo action is performed.         |
| `history:snapshot` | `{}`              | Dispatched when a history state snapshot is pushed. |
| `editor:ready`     | `{}`              | Dispatched when the editor has fully initialized.   |
| `editor:destroyed` | `{}`              | Dispatched when the editor instance is destroyed.   |

---

## PageBuilder Convenience Listeners

The object returned by `PageBuilder.init()` includes high-level convenience methods for common embedding scenarios:

```javascript
const instance = PageBuilder.init({ container: "#editor", ...config })

// Listen for page navigation/switching
instance.onPageChange(({ slug }) => {
  console.log("Active page changed to:", slug)
})

// Listen for exit action (e.g. back button in header)
instance.onExit(() => {
  console.log("User exited the editor")
})

// Listen for generic change events
instance.onChange((data) => {
  console.log("PageBuilder content changed:", data)
})
```

---

## Direct Route Integration & Customization Examples

When using Laravel Page Builder directly via Laravel routes (e.g. at `/pagebuilder`), you can listen to editor events directly in your Blade view (`layout.blade.php`) to handle navigation, URL query parameter preservation, notifications, and custom workflows.

### 1. Handling Exit Navigation & Preserving Query Params

When the user clicks the exit button in the editor header, you can perform clean redirection back to your application's dashboard while preserving designated query parameters:

```javascript
const instance = PageBuilder.init({ container: "#editor", ...config })

instance.onExit(() => {
  // Clean up query parameters except for preserved ones
  const url = new URL(window.location.href)
  const preserved = config.preservedParams || ["ref", "tenant"]
  const newParams = new URLSearchParams()

  url.searchParams.forEach((value, key) => {
    if (preserved.includes(key)) {
      newParams.set(key, value)
    }
  })

  // Redirect to destination route or updated URL
  url.pathname = "/admin/pages"
  url.search = newParams.toString()
  window.location.href = url.toString()
})
```

### 2. Updating Page Title & Breadcrumbs on Navigation

Listen to page switches to update your browser title or page heading in real-time:

```javascript
const instance = PageBuilder.init({ container: "#editor", ...config })
const editor = instance.getEditor()

instance.onPageChange(({ slug }) => {
  document.title = `Editing: ${slug} — ${config.appName || "Laravel Page Builder"}`
})
```

### 3. Tracking Changes & Auto-Save Notifications

React to content mutations to show toast notifications or track dirty state:

```javascript
const editor = instance.getEditor()

editor.on("section:added", ({ sectionId, type }) => {
  showToast(`Added new ${type} section`)
})

editor.on("page:saved", ({ slug }) => {
  showToast(`Page "${slug}" saved successfully!`, "success")
})
```
