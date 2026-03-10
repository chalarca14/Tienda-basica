<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()

    {
        $cart = $this->syncCartWithInventory(session()->get('cart', []));
        session()->put('cart', $cart);
        $total = $this->calculateTotal($cart);
        return view('cart.index', compact('cart', 'total'));
    }
    public function add(Request $request, Product $product)
    {
        if (!$product->active) {
            return redirect()->back()->with('error', 'Este producto no esta
disponible.');
        }
        if ($product->stock < 1) {
            return redirect()->back()->with('error', 'Este producto no tiene stock
disponible.');
        }
        $cart = session()->get('cart', []);
        $currentQuantity = $cart[$product->id]['quantity'] ?? 0;
        if ($currentQuantity >= $product->stock) {
            return redirect()->back()->with('error', 'No puedes agregar mas unidades
que el stock disponible.');
        }
        $cart[$product->id] = [
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $currentQuantity + 1,
            'image' => $product->image,
        ];
        session()->put('cart', $cart);
        return redirect()->back()->with('success', 'Producto agregado al carrito.');
    }
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $cart = session()->get('cart', []);
        if (!isset($cart[$id])) {
            return redirect()->route('cart.index')->with('error', 'El producto no existe
en el carrito.');
        }
        $product = Product::find($id);
        if (!$product || !$product->active || $product->stock < 1) {
            unset($cart[$id]);
            session()->put('cart', $cart);
            return redirect()->route('cart.index')->with('error', 'El producto ya no esta
disponible y fue retirado del carrito.');
        }
        if ($validated['quantity'] > $product->stock) {
            return redirect()->route('cart.index')->with('error', 'Solo hay ' . $product->stock . ' unidades disponibles.');
        }
        $cart[$id] = [
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => $validated['quantity'],
            'image' => $product->image,
        ];
        session()->put('cart', $cart);

        return redirect()->route('cart.index')->with('success', 'Cantidad
actualizada.');
    }
    public function remove($id)
    {
        $cart = session()->get('cart', []);
        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }
        return redirect()->route('cart.index');
    }
    private function calculateTotal($cart)
    {
        $total = 0;
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
    private function syncCartWithInventory(array $cart): array
    {
        if (empty($cart)) {
            return [];
        }
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $syncedCart = [];
        foreach ($cart as $id => $item) {
            $product = $products->get((int) $id);
            if (!$product || !$product->active || $product->stock < 1) {

                continue;
            }
            $quantity = max(1, min((int) $item['quantity'], $product->stock));
            $syncedCart[$id] = [
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $quantity,
                'image' => $product->image,
            ];
        }
        return $syncedCart;
    }
}
