import React, {
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
} from "react";
import { Search, ChevronLeft, ArrowRight } from "lucide-react";
import { BlockSchema } from "@/types/page-builder";
import { getBlockIcon } from "./layout/blockIcons";
import api from "@/services/api";
import { injectEditorScript } from "@/core/messaging/EditorScriptInjector";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Drawer,
    DrawerContent,
    DrawerHeader,
    DrawerTitle,
    DrawerClose,
} from "@/components/ui/drawer";
import { useBreakpoint } from "@/hooks/useBreakpoint";
import { cn } from "@/lib/utils";

interface AddBlockModalProps {
    isOpen: boolean;
    previewUrl: string;
    onClose: () => void;
    onAdd: (type: string) => void;
    blockTypes: BlockSchema[];
}

const PREVIEW_IFRAME_WIDTH = 1240;

function buildBlockPreviewPayload(schema: BlockSchema) {
    const type = schema.type || "block";
    const values: Record<string, any> = {};

    // Base defaults from settings
    (schema.settings || []).forEach((s) => {
        if (s.default !== undefined) values[s.id] = s.default;
    });

    const blocks: Record<string, any> = {};
    const order: string[] = [];

    // Override with first preset settings if available
    const preset = Array.isArray(schema.presets) ? schema.presets[0] : null;
    if (preset?.settings && typeof preset.settings === "object") {
        Object.assign(values, preset.settings);
    }

    if (Array.isArray(preset?.blocks)) {
        preset.blocks.forEach((pb: any, i: number) => {
            const blockId = `${pb.type || "block"}_preview_${i}`;
            blocks[blockId] = {
                type: pb.type,
                settings: pb.settings || {},
                blocks: pb.blocks || {},
                order: pb.order || [],
            };
            order.push(blockId);
        });
    }

    return {
        type,
        settings: values,
        blocks,
        order,
    };
}

export default function AddBlockModal({
    isOpen,
    previewUrl,
    onClose,
    onAdd,
    blockTypes,
}: AddBlockModalProps) {
    const isMobile = !useBreakpoint(768);
    const iframeRef = useRef<HTMLIFrameElement | null>(null);
    const previewContainerRef = useRef<HTMLDivElement | null>(null);
    const [iframeReady, setIframeReady] = useState(false);
    const [previewScale, setPreviewScale] = useState(1);
    const [previewContainerHeight, setPreviewContainerHeight] = useState(0);
    const [query, setQuery] = useState("");
    const [selectedType, setSelectedType] = useState<string | null>(null);
    const [hoveredType, setHoveredType] = useState<string | null>(null);
    const [previewHtml, setPreviewHtml] = useState("");
    const [isPreviewLoading, setIsPreviewLoading] = useState(false);
    const [previewError, setPreviewError] = useState<string | null>(null);
    const hoverTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    // Mobile-specific step logic
    const [step, setStep] = useState<"list" | "preview">("list");

    const filteredBlocks = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return blockTypes;
        return blockTypes.filter((block) => {
            const label = block.name.toLowerCase();
            const type = (block.type || "").toLowerCase();
            return label.includes(q) || type.includes(q);
        });
    }, [blockTypes, query]);

    const selectedBlock = useMemo(() => {
        const typeToFind = selectedType || hoveredType;
        if (!typeToFind) return filteredBlocks[0] || null;
        return (
            filteredBlocks.find((block) => block.type === typeToFind) ||
            blockTypes.find((block) => block.type === typeToFind) ||
            null
        );
    }, [blockTypes, filteredBlocks, selectedType, hoveredType]);

    const previewBlock = useMemo(() => {
        if (hoveredType) {
            return (
                filteredBlocks.find((block) => block.type === hoveredType) ||
                blockTypes.find((block) => block.type === hoveredType) ||
                null
            );
        }
        return selectedBlock;
    }, [hoveredType, filteredBlocks, blockTypes, selectedBlock]);

    const iframeHeight = useMemo(() => {
        if (previewContainerHeight <= 0 || previewScale <= 0) {
            return "100%";
        }
        return `${previewContainerHeight / previewScale}px`;
    }, [previewContainerHeight, previewScale]);

    const updatePreviewScale = useCallback(() => {
        const container = previewContainerRef.current;
        if (!container) return;

        const containerWidth = container.clientWidth;
        const containerHeight = container.clientHeight;

        if (!containerWidth || !containerHeight) return;

        const scale = containerWidth / PREVIEW_IFRAME_WIDTH;

        setPreviewScale(scale);
        setPreviewContainerHeight(containerHeight);
    }, []);

    const sendPreviewHtml = useCallback(() => {
        if (!previewBlock) return;
        const iframe = iframeRef.current;
        if (!iframe || !iframe.contentWindow || !iframe.contentDocument) return;

        injectEditorScript(iframe.contentDocument, iframe.contentWindow);
        iframe.contentWindow.postMessage(
            {
                type: "set-inspector",
                enabled: false,
            },
            "*"
        );
        iframe.contentWindow.postMessage(
            {
                type: "set-preview-html",
                html: previewHtml,
            },
            "*"
        );
    }, [previewBlock, previewHtml]);

    useEffect(() => {
        if (!isOpen) return;
        setQuery("");
        setSelectedType(blockTypes[0]?.type || null);
        setHoveredType(null);
        setPreviewError(null);
        setPreviewHtml("");
        setIframeReady(false);
        setStep("list");
    }, [isOpen, blockTypes]);

    useEffect(() => {
        if (!isOpen) return;
        if (!previewBlock) {
            setPreviewHtml("");
            setPreviewError(null);
            return;
        }

        if (previewBlock.local) {
            setPreviewHtml("");
            setPreviewError(null);
            setIsPreviewLoading(false);
            return;
        }

        let cancelled = false;

        const loadPreview = async () => {
            setIsPreviewLoading(true);
            setPreviewError(null);
            try {
                const payload = buildBlockPreviewPayload(previewBlock);
                const { html } = await api.renderBlock(payload);
                if (cancelled) return;
                setPreviewHtml(html || "");
            } catch {
                if (cancelled) return;
                setPreviewError("Unable to render block preview.");
                setPreviewHtml("");
            } finally {
                if (!cancelled) setIsPreviewLoading(false);
            }
        };

        const timer = window.setTimeout(() => {
            void loadPreview();
        }, 80);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [isOpen, previewBlock?.type]);

    useEffect(() => {
        if (!isOpen || !iframeReady) return;
        sendPreviewHtml();
    }, [isOpen, iframeReady, sendPreviewHtml]);

    useEffect(() => {
        if (!isOpen) return;

        requestAnimationFrame(() => {
            updatePreviewScale();
        });
    }, [isOpen, updatePreviewScale]);

    useEffect(() => {
        if (!isOpen) return;

        const container = previewContainerRef.current;
        if (!container) return;

        const handleResize = () => {
            requestAnimationFrame(updatePreviewScale);
        };

        handleResize();

        let resizeObserver: ResizeObserver | null = null;
        if ("ResizeObserver" in window) {
            resizeObserver = new ResizeObserver(handleResize);
            resizeObserver.observe(container);
        }

        window.addEventListener("resize", handleResize);
        return () => {
            resizeObserver?.disconnect();
            window.removeEventListener("resize", handleResize);
        };
    }, [isOpen, updatePreviewScale]);

    const handleAddBlock = (block: BlockSchema) => {
        onAdd(block.type);
        onClose();
    };

    const handleBlockClick = (block: BlockSchema) => {
        if (isMobile) {
            setSelectedType(block.type);
            setStep("preview");
        } else {
            handleAddBlock(block);
        }
    };

    const renderList = () => (
        <aside
            className={cn(
                "w-64 shrink-0 border-r border-gray-200 bg-white flex flex-col",
                isMobile && "w-full border-r-0 pb-6"
            )}
        >
            <div className="px-3 py-3 border-b border-gray-200 bg-white">
                {!isMobile && (
                    <p className="text-sm font-semibold text-gray-900 mb-2">
                        Add block
                    </p>
                )}
                <div className="relative">
                    <Search className="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Search blocks"
                        className="w-full h-9 rounded-md border border-gray-300 bg-white pl-9 pr-2 text-sm text-gray-800 outline-none focus:border-blue-500"
                    />
                </div>
            </div>

            <div className="flex-1 overflow-y-auto p-2">
                {filteredBlocks.length === 0 && (
                    <p className="text-xs text-gray-500 px-2 py-6 text-center">
                        No blocks found.
                    </p>
                )}

                {filteredBlocks.map((block) => {
                    const active = previewBlock?.type === block.type;
                    const Icon = getBlockIcon(block.type);
                    return (
                        <button
                            key={block.type}
                            onMouseEnter={() => {
                                if (isMobile) return;
                                if (hoverTimeoutRef.current)
                                    clearTimeout(hoverTimeoutRef.current);
                                hoverTimeoutRef.current = setTimeout(() => {
                                    setHoveredType(block.type);
                                    setSelectedType(block.type);
                                }, 100);
                            }}
                            onMouseLeave={() => {
                                if (isMobile) return;
                                if (hoverTimeoutRef.current)
                                    clearTimeout(hoverTimeoutRef.current);
                                setHoveredType(null);
                            }}
                            onFocus={() => setSelectedType(block.type)}
                            onClick={() => handleBlockClick(block)}
                            className={cn(
                                "w-full mb-1 flex items-center gap-2 rounded-md px-2.5 py-2 text-left text-sm transition group",
                                active && !isMobile
                                    ? "bg-gray-900 text-white"
                                    : "text-gray-700 hover:bg-gray-200",
                                isMobile && "py-3 border-b border-gray-50"
                            )}
                        >
                            <Icon className="h-4 w-4 shrink-0" />
                            <span className="truncate font-medium flex-1">
                                {block.name}
                            </span>
                            {isMobile && (
                                <ArrowRight className="h-4 w-4 text-gray-300" />
                            )}
                        </button>
                    );
                })}
            </div>
        </aside>
    );

    const renderPreview = () => (
        <section
            className={cn(
                "flex-1 min-w-0 p-3 bg-gray-50 flex flex-col",
                isMobile && "h-[60vh] bg-white p-0"
            )}
        >
            {isMobile && (
                <div className="px-3 py-3 border-b flex items-center justify-between bg-white shrink-0">
                    <button
                        onClick={() => setStep("list")}
                        className="flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900"
                    >
                        <ChevronLeft className="h-4 w-4" />
                        Back
                    </button>
                    <span className="text-sm font-bold text-gray-900">
                        {previewBlock?.name}
                    </span>
                    <button
                        onClick={() =>
                            previewBlock && handleAddBlock(previewBlock)
                        }
                        className="text-sm font-bold text-blue-600 hover:text-blue-700"
                    >
                        Add
                    </button>
                </div>
            )}

            <div className="flex-1 overflow-hidden relative p-2 flex items-center justify-center">
                {isPreviewLoading && (
                    <div className="absolute inset-0 z-10 flex items-center justify-center text-sm text-gray-500 bg-white/50">
                        Rendering preview...
                    </div>
                )}

                {previewError && !isPreviewLoading && (
                    <div className="absolute top-2 left-2 right-2 z-10 rounded-md border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs text-amber-700 text-center">
                        {previewError}
                    </div>
                )}
                <div
                    ref={previewContainerRef}
                    className="w-full max-w-[1120px] aspect-[16/10] max-h-full overflow-hidden relative min-w-0"
                    style={{ maxWidth: "100%" }}
                >
                    <iframe
                        ref={iframeRef}
                        key={previewUrl}
                        title="Block preview"
                        src={previewUrl}
                        className="block"
                        style={{
                            position: "absolute",
                            top: 0,
                            left: 0,
                            width: `${PREVIEW_IFRAME_WIDTH}px`,
                            height: iframeHeight,
                            transform: `scale(${previewScale})`,
                            transformOrigin: "top left",
                            border: "0",
                            pointerEvents: "none",
                        }}
                        sandbox="allow-same-origin allow-scripts"
                        onLoad={() => {
                            setIframeReady(true);
                            sendPreviewHtml();
                        }}
                    />
                </div>
            </div>
        </section>
    );

    if (isMobile) {
        return (
            <Drawer open={isOpen} onOpenChange={(open) => !open && onClose()}>
                <DrawerContent
                    hideOverlay={true}
                    className="max-h-[60vh] flex flex-col p-0 overflow-hidden"
                >
                    <DrawerHeader>
                        <DrawerTitle className="text-center text-base font-bold">
                            {step === "list" ? "Add Block" : "Preview Block"}
                        </DrawerTitle>
                    </DrawerHeader>

                    <div className="flex-1 overflow-hidden flex flex-col">
                        {step === "list" ? renderList() : renderPreview()}
                    </div>
                </DrawerContent>
            </Drawer>
        );
    }

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-[650px] p-0 overflow-hidden gap-0">
                <DialogHeader className="sr-only">
                    <DialogTitle>Add Block</DialogTitle>
                </DialogHeader>

                <div className="flex h-[66vh] min-h-[430px]">
                    {renderList()}
                    {renderPreview()}
                </div>
            </DialogContent>
        </Dialog>
    );
}
