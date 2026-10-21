<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;

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
        Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
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
        Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])
            ->name('sales.cancel');
    });

    Route::get('/katalog', [CatalogController::class, 'index'])
        ->name('catalog');
    Route::get('/katalog/{product:slug}', [CatalogController::class, 'show'])
        ->name('catalog.show');

    Route::middleware('role:customer')->group(function () {
        Route::get('/keranjang', [CartController::class, 'index'])
            ->name('cart.index');
        Route::post('/keranjang', [CartController::class, 'add'])
            ->name('cart.add');
        Route::patch('/keranjang/{variant}', [CartController::class, 'update'])
            ->name('cart.update');
        Route::delete('/keranjang/{variant}', [CartController::class, 'remove'])
            ->name('cart.remove');
        Route::get('/checkout', [CheckoutController::class, 'create'])
            ->name('checkout.create');
        Route::post('/checkout', [CheckoutController::class, 'store'])
            ->name('checkout.store');

        Route::get('/pesanan', [OrderController::class, 'index'])
            ->name('orders.index');
        Route::get('/pesanan/{sale}', [OrderController::class, 'show'])
            ->name('orders.show');
        Route::post('/pesanan/{sale}/batal', [OrderController::class, 'cancel'])
            ->name('orders.cancel');
    });
});