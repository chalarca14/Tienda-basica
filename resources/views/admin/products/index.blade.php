@extends('layouts.app')
@section('title', 'Administrar Productos')
@section('content')

<div class="max-w-7xl mx-auto px-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold">Administrar Productos</h1>
        <a href="{{ route('admin.products.create') }}" class="bg-blue-600
text-white py-2 px-4 rounded hover:bg-blue-700">
            Nuevo producto
        </a>
        {{-- Botón de cerrar sesión --}}
        <form method="POST" action="{{ route('admin.logout') }}" style="display:inline">
            @csrf
            <button type="submit">🚪 Cerrar sesión</button>
        </form>
    </div>
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="text-left p-3">ID</th>
                    <th class="text-left p-3">Imagen</th>
                    <th class="text-left p-3">Nombre</th>
                    <th class="text-left p-3">Precio</th>
                    <th class="text-left p-3">Stock</th>
                    <th class="text-left p-3">Estado</th>
                    <th class="text-left p-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr class="border-t">
                    <td class="p-3">{{ $product->id }}</td>
                    <td class="p-3">
                        @if($product->image)
                        <img src="{{ asset('storage/' . $product->image) }}" alt="{{
$product->name }}" class="w-12 h-12 object-cover rounded">
                        @else
                        <span class="text-gray-400">Sin imagen</span>
                        @endif
                    </td>
                    <td class="p-3">{{ $product->name }}</td>
                    <td class="p-3">${{ number_format($product->price, 2)
}}</td>
                    <td class="p-3">{{ $product->stock }}</td>

                    <td class="p-3">

                        <span class="{{ $product->active ? 'text-green-600' : 'text-
red-600' }}">

                            {{ $product->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>
                    <td class="p-3">
                        <div class="flex items-center gap-3">
                            <a href="{{ route('admin.products.edit', $product) }}"
                                class="text-blue-600 hover:text-blue-800">
                                Editar
                            </a>
                            <form action="{{ route('admin.products.destroy',$product) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button
                                    type="submit"
                                    class="text-red-600 hover:text-red-800"
                                    onclick="return confirm('¿Eliminar este producto?')">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="p-6 text-center text-gray-500">No
                        hay productos registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
@endsection