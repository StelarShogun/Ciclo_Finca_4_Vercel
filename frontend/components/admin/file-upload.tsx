"use client";

import { useRef, useState } from "react";
import { ImagePlus, X } from "lucide-react";

import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

/** Zona de subida de imágenes (drag&drop + click) con previsualización, como el FileUpload del Inertia. */
export function FileUpload({
  label,
  hint,
  accept = "image/*",
  multiple = false,
  previewUrl,
  onChange,
}: {
  label: string;
  hint?: string;
  accept?: string;
  multiple?: boolean;
  previewUrl?: string | null;
  onChange: (files: File[]) => void;
}) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [dragOver, setDragOver] = useState(false);
  const [names, setNames] = useState<string[]>([]);
  const [localPreview, setLocalPreview] = useState<string | null>(null);

  function handleFiles(fileList: FileList | null) {
    const files = fileList ? Array.from(fileList) : [];
    setNames(files.map((f) => f.name));
    // Previsualización solo para imágenes (un XML generaría un <img> roto).
    if (!multiple && files[0]?.type.startsWith("image/")) setLocalPreview(URL.createObjectURL(files[0]));
    else setLocalPreview(null);
    onChange(files);
  }

  function clear() {
    setNames([]);
    setLocalPreview(null);
    if (inputRef.current) inputRef.current.value = "";
    onChange([]);
  }

  const shownPreview = localPreview ?? previewUrl ?? null;

  return (
    <div className="space-y-1.5">
      <span className="text-sm font-medium">{label}</span>
      <div
        onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
        onDragLeave={() => setDragOver(false)}
        onDrop={(e) => { e.preventDefault(); setDragOver(false); handleFiles(e.dataTransfer.files); }}
        onClick={() => inputRef.current?.click()}
        className={cn(
          "flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-4 text-center transition-colors",
          dragOver ? "border-brand-medium bg-accent/50" : "border-input hover:border-brand-medium/60",
        )}
      >
        {shownPreview && !multiple ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={shownPreview} alt="" className="h-24 w-24 rounded-md object-cover" />
        ) : (
          <ImagePlus className="h-8 w-8 text-muted-foreground" />
        )}
        {names.length > 0 ? (
          <p className="text-xs text-muted-foreground">{multiple ? `${names.length} archivo(s)` : names[0]}</p>
        ) : (
          <p className="text-xs text-muted-foreground">{hint ?? "Arrastrá una imagen o hacé clic para elegir"}</p>
        )}
        <input ref={inputRef} type="file" accept={accept} multiple={multiple} className="hidden" onChange={(e) => handleFiles(e.target.files)} />
      </div>
      {(names.length > 0 || (localPreview && !multiple)) && (
        <Button type="button" variant="ghost" size="sm" className="h-7 text-muted-foreground" onClick={(e) => { e.stopPropagation(); clear(); }}>
          <X className="h-3 w-3" /> Quitar
        </Button>
      )}
    </div>
  );
}
