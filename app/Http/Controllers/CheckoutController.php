<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CheckoutController extends Controller
{
    public function store(): RedirectResponse
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Tu carrito esta
vacio.');
        }

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        foreach ($cart as $productId => $item) {
            $product = $products->get((int) $productId);
            $requestedQuantity = (int) ($item['quantity'] ?? 0);
            if (
                !$product || !$product->active || $product->stock <
                $requestedQuantity || $requestedQuantity < 1
            ) {
                return redirect()->route('cart.index')->with('error', 'El carrito
contiene productos sin disponibilidad. Actualiza tu carrito.');
            }
        }
        try {
            DB::transaction(function () use ($cart): void {
                $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
                $total = 0;
                foreach ($cart as $productId => $item) {
                    $product = $products->get((int) $productId);
                    $total += $product->price * (int) $item['quantity'];
                }
                $order = Order::create([
                    'user_id' => auth()->id(),
                    'total' => $total,
                    'status' => 'paid',
                    'placed_at' => now(),
                ]);
                foreach ($cart as $productId => $item) {
                    $product = Product::findOrFail($productId);
                    $quantity = (int) $item['quantity'];
                    if (!$product->active || $product->stock < $quantity) {
                        throw new RuntimeException('Stock no disponible');
                    }
                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'unit_price' => $product->price,
                        'quantity' => $quantity,
                        'subtotal' => $product->price * $quantity,
                    ]);
                    $product->decrement('stock', $quantity);
                }
            });
        } catch (RuntimeException $e) {
            return redirect()->route('cart.index')->with('error', 'No se pudo
completar la compra por cambios de disponibilidad. Intentalo de nuevo.');
        }
        session()->forget('cart');
        return redirect()->route('products.index')->with('success', 'Compra
realizada con exito.');
    }
}
