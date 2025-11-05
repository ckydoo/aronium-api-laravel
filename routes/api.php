<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SalesController;

/*
|--------------------------------------------------------------------------
| API Routes for Fiscalisation Integration
|--------------------------------------------------------------------------
|
| These routes handle incoming sales data from the Flutter fiscalisation app
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected API routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Sales endpoints
    Route::prefix('sales')->group(function () {
        
        // Store single sale
        Route::post('/', [SalesController::class, 'store']);
        
        // Store batch sales
        Route::post('/batch', [SalesController::class, 'storeBatch']);
        
        // Update fiscal data for a sale
        Route::put('/{id}/fiscal', [SalesController::class, 'updateFiscalData']);
        
        // Get all sales
        Route::get('/', [SalesController::class, 'index']);
        
        // Get single sale
        Route::get('/{id}', [SalesController::class, 'show']);
        
        // Get sales by date range
        Route::get('/range/{from}/{to}', [SalesController::class, 'getByDateRange']);
        
        // Get unfiscalized sales
        Route::get('/status/unfiscalized', [SalesController::class, 'getUnfiscalized']);
        
        // Get fiscalized sales
        Route::get('/status/fiscalized', [SalesController::class, 'getFiscalized']);
        
        // Delete a sale
        Route::delete('/{id}', [SalesController::class, 'destroy']);
    });
    
});

// Public routes (no authentication required)
Route::prefix('public')->group(function () {
    
    // Get sales summary
    Route::get('/sales/summary', [SalesController::class, 'getSummary']);
    
});