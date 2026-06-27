<?php

namespace App\Http\Controllers\Admin\Products;

use App\Actions\Admin\Products\PromoteProductGalleryToMain;
use App\Actions\Admin\Products\RemoveProductGalleryImage;
use App\Http\Controllers\Controller;

class ProductGalleryController extends Controller
{
    public function promoteToMain(int $id, int $mediaId, PromoteProductGalleryToMain $action)
    {
        try {
            return response()->json($action->handle($id, $mediaId));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo promover la imagen.',
            ], 500);
        }
    }

    public function destroy(int $id, int $mediaId, RemoveProductGalleryImage $action)
    {
        try {
            return response()->json($action->handle($id, $mediaId));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar la imagen.',
            ], 500);
        }
    }
}
