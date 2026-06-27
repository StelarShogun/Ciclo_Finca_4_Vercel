<?php

namespace App\Http\Controllers\Client;

use App\Actions\Client\Invoices\BuildInvoiceShowPage;
use App\Actions\Client\Invoices\BuildInvoicesIndexPage;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class InvoiceController extends Controller
{
    public function __construct(
        private readonly BuildInvoicesIndexPage $buildInvoicesIndexPage,
        private readonly BuildInvoiceShowPage $buildInvoiceShowPage,
    ) {}

    public function invoices(Request $request)
    {
        return $this->buildInvoicesIndexPage->handle($request);
    }

    public function invoicesHeartbeat()
    {
        /** @var Client $client */
        $client = Auth::guard('clients')->user();
        $clientId = (int) $client->user_id;

        return response()->json([
            'count' => Sale::countActiveClientInvoices($clientId),
            'unseen_history' => Sale::countUnseenInClientHistory($clientId),
            'revision' => Sale::clientInvoicesRevision($clientId),
        ]);
    }

    public function showInvoice(Sale $sale)
    {
        return $this->buildInvoiceShowPage->handle($sale);
    }

    public function printInvoice(Sale $sale)
    {
        $client = Auth::guard('clients')->user();

        if (! Gate::forUser($client)->allows('invoices.view', $sale)) {
            abort(404);
        }

        $sale->load(['saleItems.product', 'client', 'sellerAdmin']);

        return view('client.invoice-print', compact('sale'));
    }
}
