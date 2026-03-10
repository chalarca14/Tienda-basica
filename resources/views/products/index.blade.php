@extends('layouts.app')
@section('title', 'Productos')
@section('content')
<div class="max-w-7xl mx-auto px-4">
    <h1 class="text-3xl font-bold mb-8">Nuestros Productos</h1>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($products as $product)
        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
            {{-- Imagen del producto --}}
            <div class="h-48 bg-gray-200">
                @if($product->image)
                 <img src="{{ asset('storage/' . $product->image) }}"       
                    alt="{{ $product->name }}"
                    class="w-full h-full object-cover">
                    <!-- para poder mostrar las imagendes hay que ejecutar este comando php artisan storage:link  -->
                @else

                <div class="w-full h-full flex items-center justify-center text-gray-400">

                    Sin imagen
                </div>
                @endif
            </div>
            {{-- Información del producto --}}
            <div class="p-4">
                <h3 class="font-bold text-lg mb-2">{{ $product->name }}</h3>
                <p class="text-gray-600 text-sm mb-4">{{ Str::limit($product->description, 100) }}</p>
                <div class="flex items-center justify-between">

                    <span class="text-2xl font-bold text-blue-600">
                        ${{ number_format($product->price, 2) }}
                    </span>
                    <span class="text-sm {{ $product->stock > 0 ? 'text-green-600':'text-red-600' }}">
                        {{ $product->stock > 0 ? 'Stock: ' . $product->stock :'Agotado' }}
                    </span>
                </div>
                <div class="mt-4 flex space-x-2">
                    <a href="{{ route('products.show', $product) }}"
                        class="flex-1 bg-gray-200 text-gray-800 text-center py-2 rounded hover:bg-gray-300 transition">
                        Ver detalles
                    </a>
                    @if($product->stock > 0)
                    <form action="{{ route('cart.add', $product) }}"
                        method="POST">
                        @csrf
                        <button type="submit"
                            class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                            Agregar
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
</div>
@endsection