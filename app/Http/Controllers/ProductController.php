<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    // Mostrar todos los productos (tienda)
    public function index(Request $request)
{
    // Traer todas las categorías
    $categories = Category::all();

    // Consulta base (productos activos)
    $query = Product::where('active', true);

    // Si el usuario selecciona una categoría
    if ($request->category_id) {
        $query->where('category_id', $request->category_id);
    }

    // Mantener paginación
    $products = $query->paginate(12);

    return view('products.index', compact('products', 'categories'));
}
    // Mostrar detalle del producto
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    // Mostrar formulario para crear producto
public function create()
{
    $categories = Category::all(); //  traer categorías
    return view('products.create', compact('categories'));
}

// Mostrar formulario para editar producto
public function edit(Product $product)
{
    $categories = Category::all(); //  traer categorías
    return view('products.edit', compact('product', 'categories'));
}
}
