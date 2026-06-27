<?php

namespace App\Services\Admin\Products;

use App\Services\Admin\Audit\AuditLogger;
use App\Services\Shared\Security\SensitiveDataMasker;
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
            Log::warning('Audit log write failed', SensitiveDataMasker::exceptionContext($e, [
                'action_type' => $actionType,
            ]));
        }
    }
}
