@extends('layouts.app')
@section('title', 'Carrito de Compras')
@section('content')
<div class="max-w-7xl mx-auto px-4">
    <h1 class="text-3xl font-bold mb-8">Carrito de Compras</h1>
    @if(empty($cart))
    <div class="text-center py-12">
        <svg class="mx-auto h-24 w-24 text-gray-400" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">

            <path stroke-linecap="round" stroke-linejoin="round" stroke-
                width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293

2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-
4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <p class="text-gray-500 text-lg mt-4">Tu carrito está vacío</p>

        <a href="{{ route('products.index') }}" class="inline-block mt-4 bg-
blue-600 text-white py-2 px-6 rounded hover:bg-blue-700">

            Ver productos
        </a>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-md p-6">
        @foreach($cart as $id => $item)
        <div class="flex items-center py-4 border-b last:border-b-0">
            {{-- Imagen --}}
            <div class="w-20 h-20 bg-gray-200 rounded">
                @if(isset($item['image']))
                <img src="{{ asset('storage/' . $item['image']) }}" alt="{{
$item['name'] }}" class="w-full h-full object-cover rounded">

                @endif
            </div>
            {{-- Detalles --}}
            <div class="flex-1 ml-4">
                <h3 class="font-bold">{{ $item['name'] }}</h3>
                <p class="text-gray-600">${{ number_format($item['price'], 2) }}
                    c/u</p>
            </div>
            {{-- Cantidad --}}
            <div class="flex items-center space-x-2">
                <form action="{{ route('cart.update', $id) }}" method="POST"
                    class="flex items-center">
                    @csrf
                    @method('PUT')
                    <input type="number"
                        name="quantity"
                        value="{{ $item['quantity'] }}"
                        min="1"
                        class="w-16 text-center border rounded py-1">

                    <button type="submit" class="ml-2 text-blue-600 hover:text-
blue-800">

                        Actualizar
                    </button>
                </form>
                {{-- Eliminar --}}
                <form action="{{ route('cart.remove', $id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:text-red-800
ml-4">
                        Eliminar
                    </button>
                </form>
            </div>

            {{-- Subtotal --}}
            <div class="ml-4 font-bold">
                ${{ number_format($item['price'] * $item['quantity'], 2) }}
            </div>
        </div>
        @endforeach
        {{-- Total --}}
        <div class="mt-6 text-right">
            <span class="text-lg">Total:</span>
            <span class="text-2xl font-bold text-blue-600 ml-4">${{
number_format($total, 2) }}</span>
        </div>
        {{-- Botones --}}
        <div class="mt-6 flex justify-end space-x-4">

            <a href="{{ route('products.index') }}" class="bg-gray-200 text-gray-
800 py-2 px-6 rounded hover:bg-gray-300">

                Seguir comprando
            </a>
            <form action="{{ route('checkout.store') }}" method="POST">
                @csrf
                <button type="submit" class="bg-green-600 text-white py-2 px-6
rounded hover:bg-green-700">
                    Proceder al pago
                </button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection