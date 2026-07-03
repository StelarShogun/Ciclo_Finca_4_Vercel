<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

final class CatalogImportJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        $secret = (string) config('app.deploy_secret', '');
        $providedHeader = (string) ($this->header('X-Deploy-Secret') ?: $this->header('X-Internal-Key'));

        if ($secret !== '' && hash_equals($secret, $providedHeader)) {
            return true;
        }

        abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'importId' => ['required', 'string'],
            'adminId' => ['required', 'integer'],
            'storedPath' => ['required', 'string'],
            'originalName' => ['required', 'string'],
            'disk' => ['nullable', 'string'],
        ];
    }
}
