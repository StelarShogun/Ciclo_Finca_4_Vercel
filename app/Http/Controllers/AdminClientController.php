<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\AuditLogger;
use App\Support\AdminPerPage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminClientController extends Controller
{
    private const SORTABLE_COLUMNS = [
        'name',
        'first_surname',
        'second_surname',
        'gmail',
        'created_at',
        'updated_at',
        'active',
    ];

    public function index(Request $request)
    {
        $query = Client::query();

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('first_surname', 'like', "%{$search}%")
                    ->orWhere('second_surname', 'like', "%{$search}%")
                    ->orWhere('gmail', 'like', "%{$search}%");
            });
        }

        $status = $request->input('status');
        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'banned') {
            $query->where('active', false);
        }

        $createdDate = $this->normalizeDate($request->input('created_date'));
        if ($createdDate !== null) {
            $query->whereDate('created_at', $createdDate->toDateString());
        }

        $updatedDate = $this->normalizeDate($request->input('updated_date'));
        if ($updatedDate !== null) {
            $query->whereDate('updated_at', $updatedDate->toDateString());
        }

        $sort = $this->normalizeSort($request->input('sort'));
        $dir = $this->normalizeDir($request->input('dir'));

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $clients = $query
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.table_clients', compact('clients', 'sort', 'dir'));
    }

    public function ban(int $id)
    {
        $client = Client::findOrFail($id);
        $client->update(['active' => false]);
        $this->logAuditAction(
            'client_ban',
            'Cliente bloqueado por administración.',
            [
                'client_id' => (int) $client->user_id,
                'client_email' => (string) ($client->gmail ?? ''),
                'active' => false,
            ]
        );

        return response()->json(['success' => true]);
    }

    public function unban(int $id)
    {
        $client = Client::findOrFail($id);
        $client->update(['active' => true]);
        $this->logAuditAction(
            'client_unban',
            'Cliente desbloqueado por administración.',
            [
                'client_id' => (int) $client->user_id,
                'client_email' => (string) ($client->gmail ?? ''),
                'active' => true,
            ]
        );

        return response()->json(['success' => true]);
    }

    private function logAuditAction(string $actionType, string $description, array $meta = []): void
    {
        try {
            app(AuditLogger::class)->logAdminAction($actionType, 'clients', $description, $meta);
        } catch (\Throwable $e) {
            Log::warning('Client audit log write failed', [
                'action_type' => $actionType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function normalizeSort(mixed $value): string
    {
        return is_string($value) && in_array($value, self::SORTABLE_COLUMNS, true)
            ? $value
            : 'name';
    }

    private function normalizeDir(mixed $value): string
    {
        return is_string($value) && strtolower($value) === 'desc' ? 'desc' : 'asc';
    }

    private function normalizeDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
