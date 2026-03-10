<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    // Mostrar todos los productos (tienda)
    public function index()
    {
        $products = Product::where('active', true)
            ->paginate(12);
        return view('products.index', compact('products'));
    }
    // Mostrar detalle del producto
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }
}
