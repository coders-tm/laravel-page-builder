import React, { memo, useRef, useState, useEffect, KeyboardEvent } from "react";
import { useEditor, EditorContent } from "@tiptap/react";
import StarterKit from "@tiptap/starter-kit";
import Link from "@tiptap/extension-link";
import { Bold, Italic, Link as LinkIcon, Unlink, Check, X } from "lucide-react";
import { SettingSchema } from "@/types/page-builder";
import { inputCls } from "./TextField";
import { cn } from "@/lib/utils";

interface RichTextFieldProps {
  setting: SettingSchema;
  value: string;
  onChange: (val: string) => void;
}

const MenuBar = ({ editor }: { editor: any }) => {
  const [showLinkInput, setShowLinkInput] = useState(false);
  const [linkUrl, setLinkUrl] = useState("");
  const linkInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (showLinkInput) {
      setLinkUrl(editor.getAttributes("link").href ?? "");
      setTimeout(() => linkInputRef.current?.focus(), 0);
    }
  }, [showLinkInput, editor]);

  if (!editor) {
    return null;
  }

  const openLinkInput = () => {
    setShowLinkInput(true);
  };

  const applyLink = () => {
    if (linkUrl.trim() === "") {
      editor.chain().focus().extendMarkRange("link").unsetLink().run();
    } else {
      editor
        .chain()
        .focus()
        .extendMarkRange("link")
        .setLink({ href: linkUrl.trim() })
        .run();
    }
    setShowLinkInput(false);
    setLinkUrl("");
  };

  const cancelLink = () => {
    setShowLinkInput(false);
    setLinkUrl("");
    editor.chain().focus().run();
  };

  const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
    if (e.key === "Enter") {
      e.preventDefault();
      applyLink();
    } else if (e.key === "Escape") {
      e.preventDefault();
      cancelLink();
    }
  };

  const activeClass = "bg-blue-100 text-blue-600";
  const defaultClass = "text-gray-600 hover:bg-gray-100";

  if (showLinkInput) {
    return (
      <div className="flex items-center gap-1 border-b border-gray-200 bg-gray-50/50 p-1 rounded-t-md">
        <LinkIcon className="w-3.5 h-3.5 text-gray-400 ml-1 shrink-0" />
        <input
          ref={linkInputRef}
          type="url"
          value={linkUrl}
          onChange={(e) => setLinkUrl(e.target.value)}
          onKeyDown={handleKeyDown}
          placeholder="https://example.com"
          className="flex-1 text-[11px] bg-transparent outline-none border border-gray-200 rounded px-1.5 py-0.5 focus:border-blue-400 min-w-0"
        />
        <button
          type="button"
          onClick={applyLink}
          className="p-1.5 rounded-md transition-colors text-green-600 hover:bg-green-50"
          title="Apply"
        >
          <Check className="w-3.5 h-3.5" />
        </button>
        <button
          type="button"
          onClick={cancelLink}
          className="p-1.5 rounded-md transition-colors text-gray-500 hover:bg-gray-100"
          title="Cancel"
        >
          <X className="w-3.5 h-3.5" />
        </button>
      </div>
    );
  }

  return (
    <div className="flex items-center gap-1 border-b border-gray-200 bg-gray-50/50 p-1 rounded-t-md">
      <button
        type="button"
        onClick={() => editor.chain().focus().toggleBold().run()}
        disabled={!editor.can().chain().focus().toggleBold().run()}
        className={cn(
          "p-1.5 rounded-md transition-colors",
          editor.isActive("bold") ? activeClass : defaultClass,
        )}
        title="Bold"
      >
        <Bold className="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        onClick={() => editor.chain().focus().toggleItalic().run()}
        disabled={!editor.can().chain().focus().toggleItalic().run()}
        className={cn(
          "p-1.5 rounded-md transition-colors",
          editor.isActive("italic") ? activeClass : defaultClass,
        )}
        title="Italic"
      >
        <Italic className="w-3.5 h-3.5" />
      </button>
      <div className="w-px h-4 bg-gray-200 mx-1"></div>
      <button
        type="button"
        onClick={openLinkInput}
        className={cn(
          "p-1.5 rounded-md transition-colors",
          editor.isActive("link") ? activeClass : defaultClass,
        )}
        title="Add Link"
      >
        <LinkIcon className="w-3.5 h-3.5" />
      </button>
      <button
        type="button"
        onClick={() => editor.chain().focus().unsetLink().run()}
        disabled={!editor.isActive("link")}
        className={cn(
          "p-1.5 rounded-md transition-colors",
          defaultClass,
          !editor.isActive("link") && "opacity-50 cursor-not-allowed",
        )}
        title="Remove Link"
      >
        <Unlink className="w-3.5 h-3.5" />
      </button>
    </div>
  );
};

export default memo(function RichTextField({
  setting,
  value,
  onChange,
}: RichTextFieldProps) {
  const editor = useEditor({
    extensions: [
      StarterKit,
      Link.configure({
        openOnClick: false,
        HTMLAttributes: {
          class: "text-blue-600 underline",
        },
      }),
    ],
    content: value,
    onUpdate: ({ editor }) => {
      onChange(editor.getHTML());
    },
    editorProps: {
      attributes: {
        class:
          "prose prose-sm max-w-none focus:outline-none min-h-[100px] p-3 text-[12px] leading-relaxed",
      },
    },
  });

  useEffect(() => {
    if (editor && value !== editor.getHTML()) {
      editor.commands.setContent(value ?? "");
    }
  }, [value, editor]);

  return (
    <div className="flex flex-col border border-gray-200 rounded-md bg-white focus-within:ring-1 focus-within:ring-blue-500 focus-within:border-blue-500 transition-all shadow-sm">
      <MenuBar editor={editor} />
      <EditorContent
        editor={editor}
        className="overflow-y-auto w-full custom-scrollbar"
      />
    </div>
  );
});
