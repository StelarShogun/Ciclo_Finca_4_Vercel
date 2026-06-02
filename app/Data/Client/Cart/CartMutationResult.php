<?php

namespace App\Data\Client\Cart;

use Illuminate\Http\JsonResponse;

final readonly class CartMutationResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public bool $success,
        public int $status,
        public array $payload,
    ) {}

    public function toJsonResponse(): JsonResponse
    {
        return response()->json($this->payload, $this->status);
    }
}
