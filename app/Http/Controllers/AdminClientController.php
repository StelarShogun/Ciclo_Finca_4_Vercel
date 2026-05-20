<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\AuditLogger;
use App\Support\AdminPerPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminClientController extends Controller
{
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

        $perPage = AdminPerPage::resolve($request->input('per_page', 10));
        $clients = $query
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.users.table_clients', compact('clients'));
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
}
