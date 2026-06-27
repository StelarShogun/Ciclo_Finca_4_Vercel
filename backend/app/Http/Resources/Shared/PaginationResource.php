<?php

namespace App\Http\Resources\Shared;

use App\Services\Client\Inertia\ListPaginationPayload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaginationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource instanceof LengthAwarePaginator
            ? ListPaginationPayload::from($this->resource)
            : [];
    }
}
