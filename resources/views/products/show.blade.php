@extends('layouts.app')
@section('title', $product->name)
@section('content')
<div class="max-w-5xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow-md overflow-hidden md:grid md:grid-cols-2">
        <div class="bg-gray-100 h-80 md:h-full">
            @if($product->image)
            <img
                src="{{ asset('storage/' . $product->image) }}"
                alt="{{ $product->name }}"
                class="w-full h-full object-cover">
            @else

            <div class="w-full h-full flex items-center justify-center text-gray-400">

                Sin imagen
            </div>
            @endif
        </div>
        <div class="p-6">
            <h1 class="text-3xl font-bold mb-2">{{ $product->name }}</h1>
            <p class="text-sm mb-4 {{ $product->active ? 'text-green-600' :'text-red-600' }}">
                {{ $product->active ? 'Disponible' : 'No disponible' }}

            </p>
            <p class="text-gray-700 mb-6">
                {{ $product->description ?: 'Sin descripcion.' }}
            </p>
            <div class="mb-6">
                <p class="text-3xl font-bold text-blue-600">${{number_format($product->price, 2) }}</p>
                <p class="text-sm {{ $product->stock > 0 ? 'text-green-600' :'text-red-600' }}">
                    {{ $product->stock > 0 ? 'Stock: ' . $product->stock : 'Agotado'}}
                </p>
            </div>
            <div class="flex gap-3">
                <a
                    href="{{ route('products.index') }}"

                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition">
                    Volver
                </a>
                @if($product->active && $product->stock > 0)
                <form action="{{ route('cart.add', $product) }}"
                    method="POST">
                    @csrf
                    <button
                        type="submit"

                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Agregar al carrito
                    </button>
                </form>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection