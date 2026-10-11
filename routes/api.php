<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemPhotoController;
use App\Http\Controllers\Api\ItemMatchController;

Route::apiResource('categories', CategoryController::class);
Route::apiResource('items', ItemController::class);
Route::apiResource('item-photos', ItemPhotoController::class);
Route::apiResource('item-matches', ItemMatchController::class);
