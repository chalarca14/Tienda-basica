@extends('layouts.app')
@section('title', 'Productos')
@section('content')
<div class="max-w-7xl mx-auto px-4">
    <h1 class="text-3xl font-bold mb-8">Nuestros Productos</h1>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        {{-- CATEGORÍAS --}}
        <div class="md:col-span-1">
            <h2 class="text-xl font-bold mb-4">Categorías</h2>

            <a href="{{ route('products.index') }}" class="block mb-2 text-blue-600">
                Todas
            </a>

            @foreach($categories as $category)
            <a href="{{ route('products.index', ['category_id' => $category->id]) }}"
                class="block mb-2 
       {{ request('category_id') == $category->id ? 'text-blue-600 font-bold' : 'text-gray-700' }}">

                {{ $category->name }}
            </a>
            @endforeach
        </div>
        <div class="md:col-span-3">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                @forelse($products as $product)
                <div class="bg-white rounded-2xl shadow-md hover:shadow-2xl transition duration-300 overflow-hidden group">

                    <!-- Imagen -->
                    <div class="relative overflow-hidden">
                        @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}"
                            class="w-full h-48 object-cover group-hover:scale-110 transition duration-300">
                        @else
                        <div class="w-full h-48 flex items-center justify-center bg-gray-200 text-gray-400">
                            Sin imagen
                        </div>
                        @endif

                        <!-- Badge stock -->
                        @if($product->stock > 0)
                        <span class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded">
                            Disponible
                        </span>
                        @else
                        <span class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                            Agotado
                        </span>
                        @endif
                    </div>

                    <!-- Contenido -->
                    <div class="p-4 flex flex-col h-[230px]">

                        <!-- 1. INFO -->
                        <div>
                            <h3 class="font-semibold text-lg text-gray-800">
                                {{ $product->name }}
                            </h3>

                            <p class="text-gray-500 text-sm mt-1 overflow-hidden h-10">
                                {{ Str::limit($product->description, 80) }}
                            </p>
                        </div>

                        <!-- 2. PRECIO + STOCK -->
                        <div class="mt-3">
                            <span class="text-lg font-bold text-indigo-600 block">
                                ${{ number_format($product->price, 2) }}
                            </span>

                            <span class="text-xs mt-1 inline-block px-2 py-1 rounded
            {{ $product->stock > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">

                                {{ $product->stock > 0 ? $product->stock . ' disponibles' : 'Agotado' }}
                            </span>
                        </div>

                        <!-- 3. BOTONES -->
                        <div class="mt-auto flex gap-2">

                            <a href="{{ route('products.show', $product) }}"
                                class="flex-1 text-center bg-gray-100 text-gray-700 py-2 rounded-lg hover:bg-gray-200 transition text-sm">
                                Ver
                            </a>

                            @if($product->stock > 0)
                            <form action="{{ route('cart.add', $product) }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white py-2 rounded-lg hover:scale-105 transition text-sm">
                                    + Carrito
                                </button>
                            </form>
                            @endif

                        </div>

                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 text-lg">No hay productos
                        disponibles</p>
                </div>
                @endforelse

            </div>
            {{-- Paginación --}}
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        </div> {{-- cierra col-span-3 --}}
    </div> {{-- cierra grid principal --}}
</div>
@endsection