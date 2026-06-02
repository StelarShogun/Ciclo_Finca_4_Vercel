<?php

namespace App\Services\Admin\Products;

use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;

final class ProductAuditLogger
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {}

    public function log(string $actionType, string $description, array $meta = []): void
    {
        try {
            $this->auditLogger->logAdminAction($actionType, 'products', $description, $meta);
        } catch (\Throwable $e) {
            Log::warning('Audit log write failed', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
