<?php

namespace App\Http\Requests\Internal;

use Illuminate\Foundation\Http\FormRequest;

final class MediaConversionsJobRequest extends FormRequest
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
            'mediaIds' => ['nullable', 'array'],
            'mediaIds.*' => ['integer'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
