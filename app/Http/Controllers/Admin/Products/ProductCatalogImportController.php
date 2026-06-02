<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\ExportProductCatalog;
use App\Actions\Admin\Products\ImportProductCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Products\ImportCatalogRequest;
use Illuminate\Http\Request;

class ProductCatalogImportController extends Controller
{
    public function import(ImportCatalogRequest $request, ImportProductCatalog $action)
    {
        return $action->handle($request);
    }

    public function export(Request $request, ExportProductCatalog $action, ?string $format = null)
    {
        return $action->handle($request, $format);
    }
}
