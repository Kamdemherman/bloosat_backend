<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AuthController,
    ClientController,
    DashboardController,
    EncaissementController,
    InvoiceController,
    ProductController,
    RoleController,
    SiteController,
    StockController,
    SubscriptionController,
    SystemLogController,
    UserController,
    WarehouseController,
};

// ── Public ────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ── Protected (Sanctum token) ─────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // ── Dashboard ──────────────────────────────────────────────
    Route::get('/dashboard/stats',       [DashboardController::class, 'stats']);
    Route::get('/dashboard/daily-total', [DashboardController::class, 'dailyTotal']);

    // ── Clients ────────────────────────────────────────────────
    Route::apiResource('clients', ClientController::class);
    Route::patch('/clients/{client}/deactivate', [ClientController::class, 'deactivate']);
    Route::patch('/clients/{client}/suspend',    [ClientController::class, 'suspend']);
    Route::patch('/clients/{client}/unsuspend',  [ClientController::class, 'unsuspend']);

   // ── Sites ───────────────────────────────────────────────────
    Route::get('/clients/{client}/sites',        [SiteController::class, 'byClient']);
    Route::apiResource('sites', SiteController::class);
    Route::patch('/sites/{site}/restore',        [SiteController::class, 'restore']);

    // ── Invoices ───────────────────────────────────────────────
    Route::apiResource('invoices', InvoiceController::class);
    Route::patch('/invoices/{invoice}/validate',       [InvoiceController::class, 'validateInvoice']);
    Route::patch('/invoices/{invoice}/unlock-request', [InvoiceController::class, 'unlockRequest']);
    Route::patch('/invoices/{invoice}/approve-unlock', [InvoiceController::class, 'approveUnlock']);
    Route::post('/invoices/{invoice}/subscription',    [InvoiceController::class, 'createSubscription']);

    // ── Subscriptions ──────────────────────────────────────────
    Route::get('/subscriptions',                [SubscriptionController::class, 'index']);
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'show']);

    // ── Encaissements ──────────────────────────────────────────
    Route::get('/encaissements/daily-total',                  [EncaissementController::class, 'dailyTotal']);
    Route::get('/encaissements',                              [EncaissementController::class, 'index']);
    Route::post('/encaissements',                             [EncaissementController::class, 'store']);
    Route::patch('/encaissements/{encaissement}/cancel',      [EncaissementController::class, 'cancel']);
    Route::post('/encaissements/{encaissement}/send-receipt', [EncaissementController::class, 'sendReceipt']);

    // ── Products ───────────────────────────────────────────────
    Route::apiResource('products', ProductController::class);
    Route::patch('/products/{id}/restore', [ProductController::class, 'restore']);

    // ── Stock ──────────────────────────────────────────────────
    Route::get('/stock/movements',   [StockController::class, 'movements']);
    Route::post('/stock/movements',  [StockController::class, 'storeMovement']);
    Route::delete('/stock/movements/{movement}', [StockController::class, 'destroy']);
    Route::get('/stock/levels',      [StockController::class, 'levels']);
    Route::get('/stock/warehouses',  [StockController::class, 'warehouses']);
    Route::apiResource('warehouses', WarehouseController::class)->only(['index', 'store', 'show', 'update']);

    // ── Users & Roles (super_admin only) ───────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('users', UserController::class);
        Route::apiResource('roles', RoleController::class);
    });

      // ── Settings (super_admin only) ─────────────────────────────
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/settings',         [\App\Http\Controllers\SettingController::class, 'index']);
        Route::put('/settings',         [\App\Http\Controllers\SettingController::class, 'update']);
        Route::post('/settings/logo',   [\App\Http\Controllers\SettingController::class, 'updateLogo']);
    });


    // ── System Logs ────────────────────────────────────────────
    Route::get('/logs', [SystemLogController::class, 'index'])
        ->middleware('role:super_admin,dg,crd');
});
