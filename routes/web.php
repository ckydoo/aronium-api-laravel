<?php
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



Route::middleware('auth')->group(function () {
     // Main dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Sales
    Route::get('/dashboard/sales', [DashboardController::class, 'sales'])->name('dashboard.sales');
    
    // Products
    Route::get('/dashboard/products', [DashboardController::class, 'products'])->name('dashboard.products');
    
    // Inventory/Stock
    Route::get('/dashboard/inventory', [DashboardController::class, 'inventory'])->name('dashboard.inventory');
    
    // Purchases
    Route::get('/dashboard/purchases', [DashboardController::class, 'purchases'])->name('dashboard.purchases');
    
    // Z-Reports
    Route::get('/dashboard/z-reports', [DashboardController::class, 'zReports'])->name('dashboard.z-reports');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';