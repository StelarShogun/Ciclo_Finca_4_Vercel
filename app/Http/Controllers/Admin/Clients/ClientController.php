<?php

namespace App\Http\Controllers\Admin\Clients;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\AuditLogger;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Support\AdminPerPage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ClientController extends Controller
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

        return Inertia::render('Admin/Clients/Index', [
            'clients' => $clients->getCollection()->map(fn (Client $client): array => [
                'user_id' => (int) $client->user_id,
                'name' => $client->name,
                'first_surname' => $client->first_surname,
                'second_surname' => $client->second_surname,
                'gmail' => $client->gmail,
                'created_at' => optional($client->created_at)->format('d/m/Y'),
                'updated_at' => optional($client->updated_at)->format('d/m/Y'),
                'active' => (bool) $client->active,
            ])->values()->all(),
            'pagination' => ListPaginationPayload::from($clients),
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
                'created_date' => (string) $request->input('created_date', ''),
                'updated_date' => (string) $request->input('updated_date', ''),
            ],
            'sort' => $sort,
            'dir' => $dir,
        ]);
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
