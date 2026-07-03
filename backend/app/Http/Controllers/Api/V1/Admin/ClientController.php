<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clients\BanClientRequest;
use App\Models\Client;
use App\Services\Admin\Audit\AuditLogger;
use App\Services\Admin\ClientPurchaseHistoryQuery;
use App\Services\Client\Inertia\ListPaginationPayload;
use App\Services\Shared\Security\SensitiveDataMasker;
use App\Support\AdminPerPage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Clientes admin para el SPA Next: lista filtrable/ordenable, bloqueo/desbloqueo
 * (con auditoría) e historial de compras del cliente (ClientPurchaseHistoryQuery).
 * ponytail: la lista replica el controller web Inertia (sin servicio propio),
 * que se retira en Bloque 6.
 */
final class ClientController extends Controller
{
    private const SORTABLE_COLUMNS = ['name', 'first_surname', 'second_surname', 'gmail', 'created_at', 'updated_at', 'active'];

    public function index(Request $request): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Client::class);

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

        if ($created = $this->normalizeDate($request->input('created_date'))) {
            $query->whereDate('created_at', $created->toDateString());
        }
        if ($updated = $this->normalizeDate($request->input('updated_date'))) {
            $query->whereDate('updated_at', $updated->toDateString());
        }

        $sort = $this->normalizeSort($request->input('sort'));
        $dir = $this->normalizeDir($request->input('dir'));

        $clients = $query
            ->orderBy($sort, $dir)
            ->paginate(AdminPerPage::resolve($request->input('per_page', 10)))
            ->withQueryString();

        return response()->json(['data' => [
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
            ],
            'sort' => $sort,
            'dir' => $dir,
        ]]);
    }

    public function show(int $id, ClientPurchaseHistoryQuery $history): JsonResponse
    {
        Gate::forUser(Auth::guard('admin')->user())->authorize('viewAny', Client::class);

        return response()->json(['data' => $history->showPayload($id, [])]);
    }

    public function ban(BanClientRequest $request, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('ban', $client);

        $client->update(['active' => false]);
        $this->logAuditAction('client_ban', 'Cliente bloqueado por administración.', [
            'client_id' => (int) $client->user_id,
            'client_email' => (string) ($client->gmail ?? ''),
            'active' => false,
        ]);

        return response()->json(['success' => true, 'message' => 'Cliente bloqueado.']);
    }

    public function unban(BanClientRequest $request, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        Gate::forUser(Auth::guard('admin')->user())->authorize('unban', $client);

        $client->update(['active' => true]);
        $this->logAuditAction('client_unban', 'Cliente desbloqueado por administración.', [
            'client_id' => (int) $client->user_id,
            'client_email' => (string) ($client->gmail ?? ''),
            'active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Cliente desbloqueado.']);
    }

    private function logAuditAction(string $actionType, string $description, array $meta = []): void
    {
        try {
            app(AuditLogger::class)->logAdminAction($actionType, 'clients', $description, $meta);
        } catch (\Throwable $e) {
            Log::warning('Client audit log write failed', SensitiveDataMasker::exceptionContext($e, ['action_type' => $actionType]));
        }
    }

    private function normalizeSort(mixed $value): string
    {
        return is_string($value) && in_array($value, self::SORTABLE_COLUMNS, true) ? $value : 'name';
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
