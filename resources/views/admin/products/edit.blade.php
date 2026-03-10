@extends('layouts.app')

@section('title', 'Editar Producto')
@section('content')
<div class="max-w-3xl mx-auto px-4">
    <h1 class="text-3xl font-bold mb-6">Editar Producto</h1>
    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('admin.products.update', $product) }}"
            method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.products._form')
            <div class="flex items-center gap-3">
                <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                    Actualizar
                </button>
                <a href="{{ route('admin.products.index') }}" class="bg-gray-200 text-gray-800 py-2 px-4 rounded hover:bg-gray-300">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection