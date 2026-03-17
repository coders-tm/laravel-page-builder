// Editor Script & Styles to be injected into the preview Iframe

export const EDITOR_CSS = `
/* Disable pointer events on links/buttons in design mode so clicks hit our section boundaries */
.pb-design-mode a[href]:not([data-live-text-setting]),
.pb-design-mode button:not([data-pb-allow]):not([data-live-text-setting]),
.pb-design-mode form:not([data-live-text-setting]),
[data-editor-section] iframe,
[data-editor-block] iframe {
    pointer-events: none !important;
}

[data-live-text-setting] {
    pointer-events: auto !important;
}

/* Force default cursor and disable text selection */
.pb-design-mode, .pb-design-mode * {
    cursor: default !important;
    user-select: none !important;
    -webkit-user-select: none !important;
}

/* Dim disabled sections and blocks in editor preview */
.pb-disabled-section,
.pb-disabled-block {
    display: none;
}
`;

export const EDITOR_JS = `
(function() {
    if (window.__pbEditorInjected) return;
    window.__pbEditorInjected = true;

    // ── Inject Shadow DOM Inspector ──────────────────────────────────────
    var editorWrapper = document.createElement('pagebuilder-editor');
    var editorInterface = document.createElement('pagebuilder-editor-interface');
    editorInterface.setAttribute('shadow-dom', 'true');
    editorInterface.style.cssText = 'all: initial;';

    var shadow = editorInterface.attachShadow({
        mode: 'open'
    });

    var inspectorStyle = document.createElement('style');
    inspectorStyle.textContent = \`/* ── Editor selection chrome ── */
        editor-preview-inspector {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999999;
            overflow: visible;
        }
        .pb-inspector-box {
            position: absolute;
            border: 2px solid #007cf8;
            border-radius: 2px;
            pointer-events: none;
            display: none;
            transition: opacity 0.1s ease-out;
            box-sizing: border-box;
        }
        .pb-inspector-badge {
            position: absolute;
            top: -22px;
            left: -2px;
            background: #007cf8;
            color: white;
            font-size: 11px;
            font-family: system-ui, -apple-system, sans-serif;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            pointer-events: none;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pb-inspector-add-btn {
            position: absolute;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 24px;
            height: 24px;
            background: #007cf8;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
            pointer-events: auto; /* Required to intercept clicks */
            z-index: 105;
            opacity: 0;
            transition: opacity 0.1s ease-in;
        }
        /* Show add buttons when JS adds .show-add-btns class */
        .pb-inspector-box.show-add-btns .pb-inspector-add-btn {
            opacity: 1;
        }
        .pb-inspector-add-btn svg { width: 14px; height: 14px; }
        .pb-inspector-add-btn.top { top: 0; }
        .pb-inspector-add-btn.bottom { top: 100%; }
        .pb-inspector-add-btn:hover {
            transform: translate(-50%, -50%) scale(1.1);
            background: #005bd3;
        }
    \`;

    var inspectorContainer = document.createElement('editor-preview-inspector');

    var inspectorHover = document.createElement('div');
    inspectorHover.className = 'pb-inspector-box';
    inspectorHover.style.pointerEvents = 'none';

    var hoverBadge = document.createElement('div');
    hoverBadge.className = 'pb-inspector-badge';
    inspectorHover.appendChild(hoverBadge);

    var plusSvg =
        '<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';

    function emitAddAction(position) {
        if (!hoverAddContext) return;

        if (hoverAddContext.kind === 'section') {
            window.parent.postMessage({
                type: 'add-section',
                position: position,
                targetId: hoverAddContext.targetId
            }, '*');
            return;
        }

        if (hoverAddContext.kind === 'block') {
            window.parent.postMessage({
                type: 'add-block',
                position: position,
                sectionId: hoverAddContext.sectionId,
                targetId: hoverAddContext.blockId,
                parentPath: hoverAddContext.parentPath || []
            }, '*');
        }
    }

    var addTopBtn = document.createElement('div');
    addTopBtn.className = 'pb-inspector-add-btn top';
    addTopBtn.innerHTML = plusSvg;
    addTopBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        emitAddAction('before');
    });

    var addBottomBtn = document.createElement('div');
    addBottomBtn.className = 'pb-inspector-add-btn bottom';
    addBottomBtn.innerHTML = plusSvg;
    addBottomBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        emitAddAction('after');
    });

    inspectorHover.appendChild(addTopBtn);
    inspectorHover.appendChild(addBottomBtn);

    var inspectorSelected = document.createElement('div');
    inspectorSelected.className = 'pb-inspector-box';
    var selectedBadge = document.createElement('div');
    selectedBadge.className = 'pb-inspector-badge';
    inspectorSelected.appendChild(selectedBadge);

    inspectorContainer.appendChild(inspectorHover);
    inspectorContainer.appendChild(inspectorSelected);

    shadow.appendChild(inspectorStyle);
    shadow.appendChild(inspectorContainer);

    editorWrapper.appendChild(editorInterface);
    document.body.appendChild(editorWrapper);

    var currentSelectedSectionId = null;
    var currentSelectedBlockId = null;
    var hoverAddContext = null;

    // ── Helpers ──────────────────────────────────────────────────────────
    function getSectionEl(sectionId) {
        return document.querySelector('[data-section-id="' + sectionId + '"]');
    }

    function getBlockEl(blockId) {
        return document.querySelector('[data-block-id="' + blockId + '"]');
    }

    function getBlockElInSection(sectionId, blockId) {
        var sectionEl = getSectionEl(sectionId);
        if (!sectionEl) return null;
        return sectionEl.querySelector('[data-block-id="' + blockId + '"]');
    }

    function getParentPath(blockEl) {
        var parentPath = [];
        var parent = blockEl.parentElement?.closest('[data-block-id]');
        while (parent && parent.hasAttribute('data-block-id')) {
            parentPath.unshift(parent.getAttribute('data-block-id'));
            parent = parent.parentElement?.closest('[data-block-id]');
        }
        return parentPath;
    }

    function getBlockAddContext(blockEl, blockMeta) {
        var sectionEl = blockEl.closest('[data-editor-section]');
        var sectionId = sectionEl ? sectionEl.getAttribute('data-section-id') : null;
        if (sectionEl) {
            try {
                var sectionMeta = JSON.parse(sectionEl.getAttribute('data-editor-section') || '{}');
                if (sectionMeta.id) {
                    sectionId = sectionMeta.id;
                }
            } catch (e) {}
        }

        return {
            kind: 'block',
            sectionId: sectionId,
            blockId: blockMeta?.id || blockEl.getAttribute('data-block-id'),
            parentPath: getParentPath(blockEl)
        };
    }

    function setHoverAddContext(context) {
        hoverAddContext = context;
        inspectorHover.classList.toggle('show-add-btns', !!context);
    }

    var selectedResizeObserver = new ResizeObserver(function() {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(repositionSelection);
    });

    var layoutObserver = new MutationObserver(function() {
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(repositionSelection);
    });
    layoutObserver.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['style', 'class'] });

    function updateInspectorRect(box, badge, el, isBlock, meta) {
        if (!el) {
            box.style.display = 'none';
            return;
        }
        var rect = el.getBoundingClientRect();
        box.style.display = 'block';
        box.style.top = (rect.top + window.scrollY) + 'px';
        box.style.left = (rect.left + window.scrollX) + 'px';
        box.style.width = rect.width + 'px';
        box.style.height = rect.height + 'px';

        if (rect.top < 28) {
            badge.style.top = '4px';
            badge.style.left = '4px';
        } else {
            badge.style.top = '-22px';
            badge.style.left = '-2px';
        }

        if (isBlock) {
            badge.textContent = (meta?.name || meta?.label || meta?.type || 'Block');
        } else {
            badge.textContent = (meta?.name || meta?.label || meta?.type || 'Section');
        }
    }

    function clearSelections() {
        inspectorSelected.style.display = 'none';
        selectedResizeObserver.disconnect();
        currentSelectedBlockId = null;
        currentSelectedSectionId = null;
    }

    function handleHighlight(el, isBlock) {
        var metaAttr = isBlock ? 'data-editor-block' : 'data-editor-section';
        var metaStr = el.getAttribute(metaAttr);
        var meta = {};
        if (metaStr) {
            try {
                meta = JSON.parse(metaStr);
            } catch (e) {}
        }

        if (isBlock) {
            navigateToBlock(el);
            currentSelectedBlockId = meta.id;
            currentSelectedSectionId = el.closest('[data-editor-section]')?.getAttribute('data-section-id') || null;
        } else {
            currentSelectedBlockId = null;
            currentSelectedSectionId = meta.id;
        }

        updateInspectorRect(inspectorSelected, selectedBadge, el, isBlock, meta);
        selectedResizeObserver.disconnect();
        selectedResizeObserver.observe(el);

        el.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    function navigateToBlock(blockEl) {
        var slideEl = blockEl.closest('.slide, [data-block-id]');
        if (!slideEl) return;

        var parent = slideEl.parentElement;
        if (!parent) return;

        var slides = parent.querySelectorAll(':scope > .slide, :scope > [data-block-id]');
        if (slides.length <= 1) return;

        var isSlider = false;
        slides.forEach(function(s) {
            var style = window.getComputedStyle(s);
            if (style.position === 'absolute' || parseFloat(style.opacity) === 0) {
                isSlider = true;
            }
        });

        if (!isSlider) return;

        slides.forEach(function(s) {
            if (s === slideEl) {
                s.style.opacity = '1';
                s.style.zIndex = '2';
                s.style.pointerEvents = '';
            } else {
                s.style.opacity = '0';
                s.style.zIndex = '1';
                s.style.pointerEvents = 'none';
            }
        });
    }

    // ── Mouse & Click handling ────────────────────────────────────────────
    function findVisibleBlockAt(x, y, fallbackEl) {
        if (!fallbackEl) return null;
        var parent = fallbackEl.parentElement;
        if (!parent) return fallbackEl;

        var siblingBlocks = parent.querySelectorAll(':scope > [data-block-id]');
        if (siblingBlocks.length <= 1) return fallbackEl;

        var hasStacked = false;
        siblingBlocks.forEach(function(s) {
            var st = window.getComputedStyle(s);
            if (st.position === 'absolute') hasStacked = true;
        });
        if (!hasStacked) return fallbackEl;

        var bestEl = null;
        var bestOpacity = -1;
        siblingBlocks.forEach(function(s) {
            var st = window.getComputedStyle(s);
            var opacity = parseFloat(st.opacity);
            if (opacity > bestOpacity) {
                bestOpacity = opacity;
                bestEl = s;
            }
        });

        return bestEl || fallbackEl;
    }

    var rafId;
    var hoverDebounceId;
    document.addEventListener('mousemove', function(e) {
        if (!inspectorEnabled) return;
        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(function() {
            if (e.target.closest('pagebuilder-editor-interface')) {
                return;
            }

            var blockEl = e.target.closest('[data-editor-block]');
            var sectionEl = e.target.closest('[data-editor-section]');

            if (blockEl) {
                blockEl = findVisibleBlockAt(e.clientX, e.clientY, blockEl);
            }

            // Clear any pending debounce
            if (hoverDebounceId) clearTimeout(hoverDebounceId);

            if (!blockEl && !sectionEl) {
                inspectorHover.classList.remove('pb-inspector-visible');
                setTimeout(function() { inspectorHover.style.display = 'none'; }, 150);
                setHoverAddContext(null);
                return;
            }

            hoverDebounceId = setTimeout(function() {
                if (blockEl) {
                    var meta = JSON.parse(blockEl.getAttribute('data-editor-block') || '{}');
                    if (meta.id === currentSelectedBlockId) {
                        inspectorHover.classList.remove('pb-inspector-visible');
                        setTimeout(function() { inspectorHover.style.display = 'none'; }, 150);
                        setHoverAddContext(null);
                        return;
                    }
                    inspectorHover.setAttribute('data-meta', JSON.stringify(meta));
                    setHoverAddContext(getBlockAddContext(blockEl, meta));
                    updateInspectorRect(inspectorHover, hoverBadge, blockEl, true, meta);
                    return;
                }
                if (sectionEl) {
                    var meta = JSON.parse(sectionEl.getAttribute('data-editor-section') || '{}');

                    if (meta.id === currentSelectedSectionId && !currentSelectedBlockId) {
                        inspectorHover.classList.remove('pb-inspector-visible');
                        setTimeout(function() { inspectorHover.style.display = 'none'; }, 150);
                        setHoverAddContext(null);
                        return;
                    }
                    inspectorHover.setAttribute('data-meta', JSON.stringify(meta));
                    setHoverAddContext({
                        kind: 'section',
                        targetId: meta.id || sectionEl.getAttribute('data-section-id')
                    });
                    updateInspectorRect(inspectorHover, hoverBadge, sectionEl, false, meta);
                }
            }, 80);
        });
    });

    document.addEventListener('mouseleave', function() {
        if (hoverDebounceId) clearTimeout(hoverDebounceId);
        inspectorHover.classList.remove('pb-inspector-visible');
        setTimeout(function() { inspectorHover.style.display = 'none'; }, 150);
        setHoverAddContext(null);
    });

    document.addEventListener('click', function(e) {
        if (!inspectorEnabled) return;
        var blockEl = e.target.closest('[data-editor-block]');
        var sectionEl = e.target.closest('[data-editor-section]');
        var liveTextEl = e.target.closest('[data-live-text-setting]');
        var settingPath = liveTextEl ? liveTextEl.getAttribute('data-live-text-setting') : null;

        if (blockEl) {
            blockEl = findVisibleBlockAt(e.clientX, e.clientY, blockEl);
        }

        if (blockEl) {
            e.preventDefault();
            e.stopPropagation();
            var blockMeta = JSON.parse(blockEl.getAttribute('data-editor-block') || '{}');
            var sectionMeta = sectionEl ? JSON.parse(sectionEl.getAttribute('data-editor-section') || '{}') :
                null;

            // Calculate full block path
            var path = [];
            var curr = blockEl;
            while (curr && curr.hasAttribute('data-block-id')) {
                path.unshift(curr.getAttribute('data-block-id'));
                curr = curr.parentElement?.closest('[data-block-id]');
            }

            currentSelectedBlockId = blockMeta.id;
            currentSelectedSectionId = sectionMeta ? sectionMeta.id : null;

            updateInspectorRect(inspectorSelected, selectedBadge, blockEl, true, blockMeta);
            selectedResizeObserver.disconnect();
            selectedResizeObserver.observe(blockEl);

            if (inspectorHover.style.display !== 'none') inspectorHover.style.display = 'none';
            setHoverAddContext(null);

            window.parent.postMessage({
                type: 'block-selected',
                sectionId: currentSelectedSectionId,
                blockId: blockMeta.id,
                path: path.join(','),
                block: blockMeta,
                section: sectionMeta,
                focusSetting: settingPath
            }, '*');
            return;
        }

        if (sectionEl) {
            e.preventDefault();
            e.stopPropagation();
            var meta = JSON.parse(sectionEl.getAttribute('data-editor-section') || '{}');

            currentSelectedBlockId = null;
            currentSelectedSectionId = meta.id;

            updateInspectorRect(inspectorSelected, selectedBadge, sectionEl, false, meta);
            selectedResizeObserver.disconnect();
            selectedResizeObserver.observe(sectionEl);

            if (inspectorHover.style.display !== 'none') inspectorHover.style.display = 'none';
            setHoverAddContext(null);

            window.parent.postMessage({
                type: 'section-selected',
                sectionId: meta.id,
                section: meta,
                focusSetting: settingPath
            }, '*');
        }
    });

    function repositionSelection() {
        if (inspectorSelected.style.display !== 'none') {
            var el = null;
            if (currentSelectedBlockId) {
                el = getBlockEl(currentSelectedBlockId);
            } else if (currentSelectedSectionId) {
                el = getSectionEl(currentSelectedSectionId);
            }

            if (el) {
                var isBlock = !!currentSelectedBlockId;
                var metaAttr = isBlock ? 'data-editor-block' : 'data-editor-section';
                var metaStr = el.getAttribute(metaAttr);
                var meta = {};
                try {
                    meta = JSON.parse(metaStr || '{}');
                } catch (e) {}

                updateInspectorRect(inspectorSelected, selectedBadge, el, isBlock, meta);
            } else {
                inspectorSelected.style.display = 'none';
            }
        }
    }
    window.addEventListener('resize', repositionSelection);
    window.addEventListener('scroll', repositionSelection, { passive: true });

    // ── Inspector enabled state ───────────────────────────────────────────
    var inspectorEnabled = true;

    function setInspectorEnabled(enabled) {
        inspectorEnabled = enabled;
        if (!enabled) {
            // Hide all inspector overlays immediately
            inspectorHover.style.display = 'none';
            inspectorSelected.style.display = 'none';
            setHoverAddContext(null);
        }
    }

    // ── Messages from parent editor ──────────────────────────────────────
    window.addEventListener('message', function(e) {
        var msg = e.data;

        if (msg.type === 'set-inspector') {
            setInspectorEnabled(!!msg.enabled);
            return;
        }

        if (msg.type === 'highlight-section') {
            clearSelections();
            currentSelectedSectionId = msg.sectionId;
            currentSelectedBlockId = msg.blockId || null;

            if (msg.blockId) {
                var blockEl = getBlockEl(msg.blockId);
                if (blockEl) {
                    handleHighlight(blockEl, true);
                    return;
                }
            }

            var el = getSectionEl(msg.sectionId);
            if (el) {
                handleHighlight(el, false);
            }
        }

        if (msg.type === 'clear-selection') {
            clearSelections();
        }

        if (msg.type === 'hover-section') {
            if (!msg.sectionId) {
                inspectorHover.style.display = 'none';
                setHoverAddContext(null);
                return;
            }

            var el = msg.blockId ? getBlockEl(msg.blockId) : getSectionEl(msg.sectionId);
            if (el) {
                var isBlock = !!msg.blockId;
                var metaAttr = isBlock ? 'data-editor-block' : 'data-editor-section';
                var meta = JSON.parse(el.getAttribute(metaAttr) || '{}');

                if (isBlock) {
                    navigateToBlock(el);
                }

                inspectorHover.setAttribute('data-meta', JSON.stringify(meta));
                if (isBlock) {
                    setHoverAddContext(getBlockAddContext(el, meta));
                } else {
                    setHoverAddContext({
                        kind: 'section',
                        targetId: meta.id || el.getAttribute('data-section-id')
                    });
                }
                updateInspectorRect(inspectorHover, hoverBadge, el, isBlock, meta);
            } else {
                inspectorHover.style.display = 'none';
                setHoverAddContext(null);
            }
        }

        if (msg.type === 'scroll-to-section') {
            var el = getSectionEl(msg.sectionId);
            if (el) {
                el.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }
        }

        if (msg.type === 'set-preview-html') {
            var previewRoot = document.querySelector('pb-editor') || document.body;
            if (previewRoot) {
                previewRoot.innerHTML = msg.html || '';
            }
            clearSelections();
            return;
        }

        /**
         * Returns the DOM container that holds page sections.
         *
         * pageIds is the ordered array of page-only section IDs (from msg.pageOrder
         * or msg.order). We find the first one already in the DOM and return its
         * parentElement as the container.
         * Falls back to <main> or <body> when none of the IDs exist yet.
         */
        function getContainer(pageIds) {
            if (pageIds && pageIds.length > 0) {
                for (var pi = 0; pi < pageIds.length; pi++) {
                    var found = getSectionEl(pageIds[pi]);
                    if (found && found.parentElement) {
                        return found.parentElement;
                    }
                }
            }
            return document.querySelector('main') || document.body;
        }

        if (msg.type === 'update-section-html') {
            var existing = getSectionEl(msg.sectionId);
            var isNewSection = !existing;
            var temp = document.createElement('div');
            temp.innerHTML = msg.html;
            var newEl = temp.firstElementChild;

            if (newEl) {
                applyDisabledStyles(newEl);

                if (existing) {
                    // In-place update — preserve DOM position exactly.
                    existing.replaceWith(newEl);
                } else {
                    // New section: insert at the correct position using the
                    // page order hint provided alongside the HTML.
                    // msg.pageOrder is the full ordered array of page section IDs
                    // (same value that reorder-sections will receive immediately
                    // after this message). We find our position in that list and
                    // look for the nearest existing successor to insert before,
                    // falling back to appending at the end of the container.
                    var container = getContainer(msg.pageOrder);
                    var inserted = false;

                    if (msg.pageOrder && msg.pageOrder.length > 0) {
                        var myIdx = msg.pageOrder.indexOf(msg.sectionId);
                        if (myIdx !== -1) {
                            // Walk forward through the ordered list to find a
                            // successor that is already in the container DOM.
                            for (var oi = myIdx + 1; oi < msg.pageOrder.length; oi++) {
                                var successorEl = getSectionEl(msg.pageOrder[oi]);
                                // Make sure the successor is in the same container.
                                if (successorEl && successorEl.parentElement === container) {
                                    container.insertBefore(newEl, successorEl);
                                    inserted = true;
                                    break;
                                }
                            }
                        }
                    }

                    if (!inserted) {
                        container.appendChild(newEl);
                    }
                }

                if (currentSelectedSectionId === msg.sectionId) {
                    if (currentSelectedBlockId) {
                        var bEl = getBlockEl(currentSelectedBlockId);
                        if (bEl) handleHighlight(bEl, true);
                    } else {
                        if (isNewSection) {
                            // Defer scrollIntoView so reorder-sections (sent
                            // immediately after this message) can finalise the
                            // order before we scroll.
                            window.__pbPendingScrollSectionId = msg.sectionId;
                        } else {
                            handleHighlight(newEl, false);
                        }
                    }
                }
            }

            broadcastLiveTextPaths();
        }

        if (msg.type === 'reorder-sections') {
            // Only move sections whose IDs appear in msg.order (page sections).
            // Layout sections are not in msg.order and must stay untouched.
            //
            // Strategy: collect all page-section elements in their new order,
            // then insert them sequentially before the first non-page-section
            // sibling that follows the last page section (typically the footer
            // layout section). This avoids blindly appending and accidentally
            // placing page sections after footer/script elements.
            var container = getContainer(msg.order);
            var pageEls = [];
            msg.order.forEach(function(sectionId) {
                var el = getSectionEl(sectionId);
                if (el) pageEls.push(el);
            });

            if (pageEls.length > 0) {
                // Determine the insertion anchor: find the DOM element that
                // should come AFTER the last page section. This is the next
                // sibling of the current last page section element that is NOT
                // itself a page section in our order (i.e. a layout footer or
                // other structural element).
                // We look through all [data-section-id] elements in the container
                // to find one NOT in msg.order that appears after any page section.
                var orderSet = {};
                msg.order.forEach(function(id) { orderSet[id] = true; });

                var anchor = null;
                var allSections = container.querySelectorAll(':scope > [data-section-id]');
                var foundLastPage = false;
                for (var si = 0; si < allSections.length; si++) {
                    var secId = allSections[si].getAttribute('data-section-id');
                    if (orderSet[secId]) {
                        foundLastPage = true;
                    } else if (foundLastPage) {
                        // This is the first non-page section after all page sections
                        anchor = allSections[si];
                        break;
                    }
                }

                // Now insert each page section in order before the anchor
                // (or append if no anchor found).
                pageEls.forEach(function(el) {
                    if (anchor) {
                        container.insertBefore(el, anchor);
                    } else {
                        container.appendChild(el);
                    }
                });
            }

            // If a new section was just added, scroll to it now that ordering is settled.
            if (window.__pbPendingScrollSectionId) {
                var pendingId = window.__pbPendingScrollSectionId;
                window.__pbPendingScrollSectionId = null;
                var targetEl = getSectionEl(pendingId);
                if (targetEl) {
                    handleHighlight(targetEl, false);
                }
            }
        }

        if (msg.type === 'reorder-blocks') {
            // Reorder block DOM nodes inside their parent without a server round-trip.
            // msg.sectionId   — the section that owns the blocks
            // msg.parentBlockId — optional; the container block (e.g. a row) whose
            //                     direct children should be reordered. When absent,
            //                     the section element itself is the parent.
            // msg.order       — ordered array of block IDs
            //
            // The block elements may be deeply nested inside intermediate wrapper
            // elements (e.g. section > .container > .grid > [data-block-id]).
            // Instead of assuming block elements are direct children of the
            // section/block parent, we find the REAL common parent: the
            // parentElement of the first block element in the order list.

            var rootEl = msg.parentBlockId
                ? getBlockEl(msg.parentBlockId)
                : getSectionEl(msg.sectionId);

            if (rootEl && msg.order.length > 0) {
                // Find the first block element to determine the real DOM parent
                var firstBlockEl = rootEl.querySelector('[data-block-id="' + msg.order[0] + '"]');
                if (firstBlockEl) {
                    var realParent = firstBlockEl.parentElement;
                    msg.order.forEach(function(blockId) {
                        var blockEl = rootEl.querySelector('[data-block-id="' + blockId + '"]');
                        if (!blockEl) return;
                        // The block element should be a direct child of realParent.
                        // If it's wrapped, walk up to the direct child of realParent.
                        var child = blockEl;
                        while (child.parentElement && child.parentElement !== realParent) {
                            child = child.parentElement;
                        }
                        if (child.parentElement === realParent) {
                            realParent.appendChild(child);
                        }
                    });
                }
            }
        }

        if (msg.type === 'remove-section') {
            var el = getSectionEl(msg.sectionId);
            // Only remove it if it exists (layout sections simply won't be
            // in msg.sectionId since the store guards against that).
            if (el) el.remove();
        }

        if (msg.type === 'remove-block') {
            var blockEl = msg.sectionId
                ? getBlockElInSection(msg.sectionId, msg.blockId)
                : getBlockEl(msg.blockId);
            if (blockEl) {
                blockEl.remove();
            }

            if (currentSelectedBlockId === msg.blockId) {
                clearSelections();
            }
            return;
        }

        if (msg.type === 'replace-all-sections') {
            // keepIds contains only page-section IDs.
            // Remove stale page sections that are no longer in the snapshot,
            // but NEVER remove layout sections (header, footer, etc.) which
            // are rendered by the Blade layout template.
            var keepIds = msg.order || [];
            var keepSet = {};
            keepIds.forEach(function(id) { keepSet[id] = true; });

            // Also build a set of layout section IDs so we can protect them.
            var layoutIds = msg.layoutIds || [];
            var layoutSet = {};
            layoutIds.forEach(function(id) { layoutSet[id] = true; });

            var container = getContainer(keepIds);
            var allSections = container.querySelectorAll('[data-section-id]');
            allSections.forEach(function(el) {
                var id = el.getAttribute('data-section-id');
                if (!keepSet[id] && !layoutSet[id]) {
                    el.remove();
                }
            });
        }

        if (msg.type === 'reload-preview') {
            window.location.reload();
        }

        /**
         * toggle-visibility — instantly show/hide a section or block in the
         * canvas by toggling the pb-disabled-* CSS classes.  No server
         * round-trip required; the store already holds the new disabled state.
         *
         * msg.kind      'section' | 'block'
         * msg.sectionId the section that owns the element
         * msg.blockId   (block only) the block to toggle
         * msg.disabled  the NEW disabled value (true = hidden)
         */
        if (msg.type === 'toggle-visibility') {
            var isDisabled = !!msg.disabled;
            if (msg.kind === 'section') {
                var sectionEl = getSectionEl(msg.sectionId);
                if (sectionEl) {
                    sectionEl.classList.toggle('pb-disabled-section', isDisabled);
                }
            } else if (msg.kind === 'block') {
                var blockEl = getBlockEl(msg.blockId);
                if (blockEl) {
                    blockEl.classList.toggle('pb-disabled-block', isDisabled);
                }
            }
            return;
        }

        if (msg.type === 'update-live-text') {
            var els = document.querySelectorAll('[data-live-text-setting="' + msg.path + '"]');
            els.forEach(function(el) {
                el.innerHTML = msg.value;
            });
        }

        if (msg.type === 'update-css-var') {
            console.log(msg)
            document.documentElement.style.setProperty(msg.cssVar, msg.value);
        }
    });

    // ── Signal ready ──────────────────────────────────────────────────────
    function applyDisabledStyles(rootEl) {
        var sectionAttr = rootEl.getAttribute('data-editor-section');
        if (sectionAttr) {
            try {
                var sectionMeta = JSON.parse(sectionAttr);
                if (sectionMeta.disabled) {
                    rootEl.classList.add('pb-disabled-section');
                } else {
                    rootEl.classList.remove('pb-disabled-section');
                }
            } catch (e) {}
        }

        var blockEls = rootEl.querySelectorAll('[data-editor-block]');
        blockEls.forEach(function(bEl) {
            try {
                var blockMeta = JSON.parse(bEl.getAttribute('data-editor-block'));
                if (blockMeta.disabled) {
                    bEl.classList.add('pb-disabled-block');
                } else {
                    bEl.classList.remove('pb-disabled-block');
                }
            } catch (e) {}
        });
    }

    document.querySelectorAll('[data-editor-section]').forEach(function(el) {
        applyDisabledStyles(el);
    });

    function broadcastLiveTextPaths() {
        var els = document.querySelectorAll('[data-live-text-setting]');
        var paths = [];
        els.forEach(function(el) {
            var p = el.getAttribute('data-live-text-setting');
            if (p) paths.push(p);
        });
        window.parent.postMessage({ type: 'live-text-paths', paths: paths }, '*');
    }

    broadcastLiveTextPaths();

    window.parent.postMessage({
        type: 'preview-ready'
    }, '*');
})();
`;
