<?php

namespace App\Http\Requests\Admin\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class ImportCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->postTooLarge()) {
            throw ValidationException::withMessages([
                'import_file' => [
                    sprintf(
                        'El archivo supera el límite POST del servidor (%s). Reducí el ZIP o aumentá post_max_size en PHP.',
                        ini_get('post_max_size') ?: 'desconocido',
                    ),
                ],
            ]);
        }

        $file = $this->file('import_file');
        if (! $file instanceof UploadedFile) {
            return;
        }

        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'import_file' => [$this->uploadErrorMessage($file)],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $usingBlobPath = $this->filled('blob_path');

        return [
            'import_file' => [
                $usingBlobPath ? 'nullable' : 'required',
                'file',
                'max:102400',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());
                    if (! in_array($extension, ['zip', 'json', 'xml', 'csv', 'txt'], true)) {
                        $fail('El archivo debe ser ZIP, JSON, XML o CSV.');
                    }
                },
            ],
            'blob_path' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    if (! config('vercel.enabled')) {
                        $fail('La importación directa por Blob solo está disponible en Vercel.');

                        return;
                    }

                    $path = (string) $value;
                    $prefix = trim((string) config('vercel.import_prefix', 'catalog-imports'), '/').'/';
                    if (! str_starts_with($path, $prefix)) {
                        $fail('La ruta del archivo importado no es válida.');

                        return;
                    }

                    if (! $this->hasAllowedExtension($path, (string) $this->input('original_name', ''))) {
                        $fail('El archivo debe ser ZIP, JSON, XML o CSV.');
                    }
                },
            ],
            'blob_url' => [
                'nullable',
                'url',
                'max:1000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $host = parse_url((string) $value, PHP_URL_HOST);
                    if (! is_string($host) || ! str_ends_with($host, '.blob.vercel-storage.com')) {
                        $fail('La URL del archivo importado no pertenece a Vercel Blob.');
                    }
                },
            ],
            'original_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->hasFile('import_file') || $this->filled('blob_path')) {
                return;
            }

            $validator->errors()->add('import_file', 'Seleccioná un archivo ZIP, JSON, XML o CSV para importar.');
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'import_file.required' => 'Seleccioná un archivo ZIP, JSON, XML o CSV para importar.',
            'import_file.file' => 'No se pudo recibir el archivo. Verificá que no supere el límite del servidor.',
            'import_file.max' => 'El archivo supera el tamaño máximo permitido (100 MB).',
        ];
    }

    private function hasAllowedExtension(string ...$filenames): bool
    {
        foreach ($filenames as $filename) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if (in_array($extension, ['zip', 'json', 'xml', 'csv', 'txt'], true)) {
                return true;
            }
        }

        return false;
    }

    private function postTooLarge(): bool
    {
        if ($this->hasFile('import_file')) {
            return false;
        }

        $contentLength = (int) $this->server('CONTENT_LENGTH', 0);
        if ($contentLength <= 0) {
            return false;
        }

        if (! str_contains((string) $this->header('Content-Type'), 'multipart/form-data')) {
            return false;
        }

        return $contentLength > $this->parseIniSize((string) ini_get('post_max_size'));
    }

    private function uploadErrorMessage(UploadedFile $file): string
    {
        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE => sprintf(
                'El archivo supera upload_max_filesize (%s). Tu ZIP debe ser más chico o hay que subir el límite en PHP.',
                ini_get('upload_max_filesize') ?: 'desconocido',
            ),
            UPLOAD_ERR_FORM_SIZE => sprintf(
                'El archivo supera el límite del formulario/post_max_size (%s).',
                ini_get('post_max_size') ?: 'desconocido',
            ),
            UPLOAD_ERR_PARTIAL => 'La subida se interrumpió a mitad de camino. Intentá de nuevo.',
            UPLOAD_ERR_NO_FILE => 'No se recibió ningún archivo en el servidor.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'El servidor no pudo guardar el archivo temporal. Contactá al administrador.',
            default => 'No se pudo subir el archivo (código '.$file->getError().').',
        };
    }

    private function parseIniSize(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) substr($value, 0, -1);

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => (float) $value,
        };
    }
}
