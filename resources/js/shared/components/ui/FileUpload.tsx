import { useRef, useState } from 'react';
import type { ChangeEvent, DragEvent } from 'react';

type FileUploadProps = {
  id: string;
  label: string;
  accept?: string;
  multiple?: boolean;
  icon?: string;
  hint?: string;
  /** URL de imagen ya existente (modo edición). */
  previewUrl?: string | null;
  onChange: (files: FileList | null) => void;
};

/**
 * Zona de subida estilizada (drag & drop + clic), legible en claro y oscuro.
 * Sustituye los <input type="file"> nativos. El input real queda oculto
 * accesiblemente y se activa con la etiqueta.
 */
export function FileUpload({
  id,
  label,
  accept,
  multiple = false,
  icon = 'fa-cloud-arrow-up',
  hint,
  previewUrl = null,
  onChange,
}: FileUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [names, setNames] = useState<string[]>([]);
  const [dragover, setDragover] = useState(false);

  function handleFiles(files: FileList | null) {
    setNames(files ? Array.from(files).map((f) => f.name) : []);
    onChange(files);
  }

  function onInputChange(event: ChangeEvent<HTMLInputElement>) {
    handleFiles(event.target.files);
  }

  function onDrop(event: DragEvent<HTMLLabelElement>) {
    event.preventDefault();
    setDragover(false);
    if (event.dataTransfer.files?.length && inputRef.current) {
      inputRef.current.files = event.dataTransfer.files;
      handleFiles(event.dataTransfer.files);
    }
  }

  function clear() {
    if (inputRef.current) {
      inputRef.current.value = '';
    }
    handleFiles(null);
  }

  const hasSelection = names.length > 0;

  return (
    <div className={`cf-file-upload-field${hasSelection ? ' is-file-selected' : ''}`}>
      <label
        htmlFor={id}
        className={`cf-file-upload${dragover ? ' is-dragover' : ''}`}
        onDragOver={(e) => {
          e.preventDefault();
          setDragover(true);
        }}
        onDragLeave={() => setDragover(false)}
        onDrop={onDrop}
      >
        <i className={`fas ${icon} cf-file-upload__icon`} aria-hidden="true" />
        <span className="cf-file-upload__text">{label}</span>
        {hint ? <span className="cf-file-upload__hint">{hint}</span> : null}
      </label>
      <input
        ref={inputRef}
        id={id}
        type="file"
        className="cf-file-upload__input"
        accept={accept}
        multiple={multiple}
        onChange={onInputChange}
      />

      {(hasSelection || previewUrl) && (
        <div className="cf-file-upload-meta">
          {previewUrl && !hasSelection ? (
            <img src={previewUrl} alt="" className="cf-file-upload-meta__thumb" />
          ) : (
            <span className="cf-file-upload-meta__thumb cf-file-upload-meta__thumb--icon" aria-hidden="true">
              <i className="fas fa-file-image" />
            </span>
          )}
          <div className="cf-file-upload-meta__body">
            <div className="cf-file-upload-meta__name">
              {hasSelection ? names.join(', ') : 'Imagen actual'}
            </div>
            <div className="cf-file-upload-meta__size">
              {hasSelection
                ? `${names.length} archivo${names.length > 1 ? 's' : ''} seleccionado${names.length > 1 ? 's' : ''}`
                : 'Sube un archivo para reemplazarla'}
            </div>
          </div>
          {hasSelection ? (
            <button type="button" className="cf-file-upload-meta__remove" onClick={clear}>
              Quitar
            </button>
          ) : null}
        </div>
      )}
    </div>
  );
}
