<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class InvoiceController extends Controller
{
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
