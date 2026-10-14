<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ItemPhotoController;
use App\Http\Controllers\Api\ItemMatchController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\DB;

Route::post('login', [UserController::class, 'login']);
Route::apiResource('users', UserController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('items', ItemController::class);
Route::apiResource('item-photos', ItemPhotoController::class);
Route::apiResource('item-matches', ItemMatchController::class);
Route::patch('items/{item}/cancel', [
    ItemController::class,
    'cancel',
]);
Route::put(
    'item-matches/{itemMatch}/respond',
    [ItemMatchController::class, 'respond']
);
Route::put(
    'item-matches/{itemMatch}/start-handover',
    [ItemMatchController::class, 'startHandover']
);
Route::put(
    'item-matches/{itemMatch}/confirm-handover',
    [ItemMatchController::class, 'confirmHandover']
);

Route::patch(
    'item-matches/{itemMatch}/mark-resolved',
    [ItemMatchController::class, 'markResolved']
);
