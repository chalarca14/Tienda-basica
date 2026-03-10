@php
$isEdit = isset($product);
@endphp
<div class="space-y-4">
    <div>

        <label for="name" class="block text-sm font-medium text-gray-
700">Nombre</label>

        <input
            type="text"
            id="name"
            name="name"
            value="{{ old('name', $product->name ?? '') }}"
            required
            class="mt-1 block w-full border rounded px-3 py-2">
    </div>
    <div>

        <label for="description" class="block text-sm font-medium text-
gray-700">Descripcion</label>

        <textarea
            id="description"
            name="description"
            rows="4"
            class="mt-1 block w-full border rounded px-3 py-2">{{ old('description', $product->description ?? '') }}</textarea>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>

            <label for="price" class="block text-sm font-medium text-gray-
700">Precio</label>

            <input
                type="number"
                step="0.01"
                min="0"
                id="price"
                name="price"
                value="{{ old('price', $product->price ?? '') }}"
                required
                class="mt-1 block w-full border rounded px-3 py-2">
        </div>
        <div>

            <label for="stock" class="block text-sm font-medium text-gray-
700">Stock</label>

            <input
                type="number"
                min="0"
                id="stock"
                name="stock"
                value="{{ old('stock', $product->stock ?? 0) }}"
                required
                class="mt-1 block w-full border rounded px-3 py-2">
        </div>
    </div>
    <div>

        <label for="image" class="block text-sm font-medium text-gray-700">Imagen</label>

        <input
            type="file"
            id="image"
            name="image"
            accept="image/*"
            class="mt-1 block w-full border rounded px-3 py-2">
        @if($isEdit && $product->image)
        <div class="mt-2">
            <p class="text-sm text-gray-600 mb-1">Imagen actual</p>
            <img src="{{ asset('storage/' . $product->image) }}" alt="{{$product->name }}" class="w-24 h-24 object-cover rounded">
        </div>
        @endif
    </div>
    <div class="flex items-center gap-2">
        <input
            type="checkbox"
            id="active"
            name="active"

            value="1"
            {{ old('active', $product->active ?? true) ? 'checked' : '' }}
            class="rounded border-gray-300">
        <label for="active" class="text-sm text-gray-700">Producto
            activo</label>
    </div>
</div>