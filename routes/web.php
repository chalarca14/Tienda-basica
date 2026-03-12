<?php
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Admin\AdminProductController; // ← corregido (Admin\)
use App\Http\Controllers\Admin\AuthController;         // ← nuevo
use Illuminate\Support\Facades\Route;

// ── Página principal ──
Route::get('/', [ProductController::class, 'index'])->name('home');

// ── Productos públicos ──
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// ── Carrito ──
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// ── Admin login (público, sin middleware) ──
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',  [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ── Admin panel (protegido con middleware is_admin) ──
Route::prefix('admin')->name('admin.')->middleware('is_admin')->group(function () {
    Route::resource('products', AdminProductController::class)->except(['show']);
});