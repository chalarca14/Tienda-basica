<?php
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AdminProductController; 
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;
// Página principal - Tienda
Route::get('/', [ProductController::class, 'index'])->name('home');
// Productos
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
// Carrito de compras
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
// Administracion de productos
Route::prefix('admin')->name('admin.')->group(function () {
Route::resource('products', AdminProductController::class)->except(['show']);
});