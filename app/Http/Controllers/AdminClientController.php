<?php

namespace App\Http\Controllers;

use App\Models\Client;

class AdminClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('name')->get();

        return view('admin.users.table_clients', compact('clients'));
    }

    public function ban(int $id)
    {
        $client = Client::findOrFail($id);
        $client->update(['active' => false]);

        return response()->json(['success' => true]);
    }

    public function unban(int $id)
    {
        $client = Client::findOrFail($id);
        $client->update(['active' => true]);

        return response()->json(['success' => true]);
    }
}
