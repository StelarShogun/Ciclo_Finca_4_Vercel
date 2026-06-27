<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Audit\AuditLogIndexRequest;
use App\Services\Admin\Audit\AuditLogPresenter;
use App\Services\Admin\Audit\AuditLogQuery;
use Inertia\Inertia;

final class AuditLogController extends Controller
{
    public function index(AuditLogIndexRequest $request, AuditLogQuery $query)
    {
        return Inertia::render('Admin/Reports/AuditLog', $query->indexPayload($request->validated()));
    }

    public static function actionTypeLabel(string $value): string
    {
        return AuditLogPresenter::actionTypeLabel($value);
    }

    public static function moduleLabel(string $value): string
    {
        return AuditLogPresenter::moduleLabel($value);
    }

    public static function descriptionLabel(?string $value): string
    {
        return AuditLogPresenter::descriptionLabel($value);
    }
}
