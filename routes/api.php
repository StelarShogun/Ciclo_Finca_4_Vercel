<?php

use App\Http\Controllers\Admin\Products\ProductController;
use Illuminate\Support\Facades\Route;

Route::apiResource('products', ProductController::class);
