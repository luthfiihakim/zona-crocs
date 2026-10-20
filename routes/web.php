<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PurchaseController;

Route::get('/', function () {
    $user = auth()->user();

    if (! $user) {
        return redirect()->route('login');
    }

    return redirect()->route($user->isStaff() ? 'dashboard' : 'catalog');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('role:admin,staff')->group(function () {
        Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
        Route::resource('categories', CategoryController::class)->except(['show', 'destroy']);
        Route::resource('suppliers', SupplierController::class)->except(['show', 'destroy']);
        Route::resource('products', ProductController::class)->except(['destroy']);
        Route::post('/products/{product}/variants', [ProductVariantController::class, 'store'])
            ->name('products.variants.store');
        Route::get('/variants/{variant}/edit', [ProductVariantController::class, 'edit'])->name('variants.edit');
        Route::put('/variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
        Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->name('categories.destroy');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
            ->name('suppliers.destroy');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])
            ->name('products.destroy');
        Route::delete('/variants/{variant}', [ProductVariantController::class, 'destroy'])
            ->name('variants.destroy');
        Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])
            ->name('purchases.cancel');
    });

    Route::middleware('role:customer')
        ->get('/katalog', fn () => view('catalog'))
        ->name('catalog');
});