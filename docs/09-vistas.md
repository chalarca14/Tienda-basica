# 🎨 Vistas (Lo que ve el usuario)

> Aquí se explica cómo están construidas las páginas HTML del proyecto y cómo modificarlas.

---

## ¿Qué son las vistas en Laravel?

Las vistas son los archivos que generan el HTML que ve el usuario en el navegador. En este proyecto se usan vistas **Blade**, que es el sistema de plantillas de Laravel.

Blade permite mezclar HTML con código PHP usando directivas que empiezan con `@`:

```php
@if($producto->active)       // si el producto está activo
    <p>Disponible</p>
@else
    <p>No disponible</p>
@endif

@foreach($products as $product)  // recorrer lista de productos
    <p>{{ $product->name }}</p>   // mostrar el nombre
@endforeach
```

> 💡 `{{ }}` es la forma de Blade de mostrar un valor. Es equivalente a `<?php echo ?>` en PHP normal, pero además protege contra ataques XSS escapando los caracteres especiales.

---

## Estructura de las vistas

```
resources/views/
├── layouts/
│   └── app.blade.php          ← plantilla base (navbar, estilos, mensajes)
├── products/
│   ├── index.blade.php        ← catálogo de productos
│   └── show.blade.php         ← detalle de un producto
├── cart/
│   └── index.blade.php        ← página del carrito
└── admin/
    ├── login.blade.php        ← formulario de login del admin
    └── products/
        ├── index.blade.php    ← lista de productos en el admin
        ├── create.blade.php   ← formulario crear producto
        ├── edit.blade.php     ← formulario editar producto
        └── _form.blade.php    ← formulario reutilizable (usado por create y edit)
```

---

## Cómo funciona el sistema de plantillas

El proyecto usa un patrón de **herencia de plantillas**:

```
layouts/app.blade.php      ← plantilla PADRE (estructura base)
    └── products/index.blade.php  ← vista HIJA (contenido específico)
    └── products/show.blade.php   ← vista HIJA
    └── cart/index.blade.php      ← vista HIJA
    └── admin/products/index.blade.php  ← vista HIJA
```

La vista hija le dice a Laravel qué plantilla usar con `@extends`, y llena los espacios definidos por `@yield` con `@section`:

```php
// En la plantilla padre (app.blade.php):
@yield('title')    // ← espacio reservado para el título
@yield('content')  // ← espacio reservado para el contenido

// En la vista hija:
@extends('layouts.app')              // usar app.blade.php como base
@section('title', 'Mi Página')       // llenar el espacio del título
@section('content')                  // llenar el espacio del contenido
    <h1>Hola</h1>
@endsection
```

---

## Vista: `layouts/app.blade.php` — Plantilla base

Esta es la plantilla que todas las páginas comparten. Define la estructura HTML completa con navbar, estilos y mensajes.

### Estilos y scripts

```php
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="{{ asset('css/styles.css') }}">
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

El proyecto usa **tres fuentes de estilos**:

| Fuente | ¿Qué es? | ¿Cómo funciona? |
|---|---|---|
| Tailwind CDN | Framework de CSS con clases utilitarias | Se carga desde internet, no requiere instalación |
| `styles.css` | CSS personalizado del proyecto | Archivo en `public/css/styles.css` |
| Vite (app.css) | CSS compilado por Laravel | Solo carga si el proyecto está compilado con `npm run dev` |

> 💡 **¿Qué es Tailwind CSS?**  
> Es un framework de estilos donde en lugar de escribir CSS, agregas clases directamente en el HTML. Por ejemplo: `class="text-blue-600 font-bold"` pone el texto azul y en negrita.

### Navbar

```php
<nav class="bg-white shadow-lg">
    <a href="{{ route('home') }}">Mi Tienda</a>
    <a href="{{ route('products.index') }}">Productos</a>
    <a href="{{ route('admin.products.index') }}">Administrar</a>

    {{-- Ícono del carrito con contador --}}
    <a href="{{ route('cart.index') }}">
        🛒
        @if(session()->has('cart') && count(session('cart')) > 0)
            <span>{{ count(session('cart')) }}</span>  ← número de items
        @endif
    </a>
</nav>
```

**El contador del carrito:**
```php
@if(session()->has('cart') && count(session('cart')) > 0)
    <span>{{ count(session('cart')) }}</span>
@endif
```
Muestra el número de **tipos de productos** en el carrito (no la cantidad total). Si tienes 2 camisas y 1 pantalón, muestra `2` (dos productos distintos).

> ⚠️ **Nota importante:** El enlace "Administrar" en el navbar lleva directamente a `/admin/products`. Si el usuario no está autenticado como admin, el middleware lo redirigirá al login automáticamente.

### Mensajes flash

```php
@if(session('success'))
    <div class="bg-green-100...">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="bg-red-100...">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="bg-red-100...">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </div>
@endif
```

Los mensajes flash son mensajes temporales que aparecen una sola vez después de una acción:

| Tipo | Color | ¿Cuándo aparece? |
|---|---|---|
| `session('success')` | Verde | Cuando una operación fue exitosa |
| `session('error')` | Rojo | Cuando hubo un error de negocio |
| `$errors->any()` | Rojo | Cuando falló la validación de un formulario |

> 💡 Los mensajes se envían desde los controladores así:  
> `return redirect()->back()->with('success', 'Producto agregado.');`  
> `return redirect()->back()->with('error', 'Sin stock.');`

---

## Vista: `products/index.blade.php` — Catálogo

Muestra todos los productos activos en una grilla.

### Estructura de la grilla

```php
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
```

Con Tailwind, esta línea define una grilla responsiva:
- En móvil: 1 columna
- En tablet (`md`): 3 columnas
- En escritorio (`lg`): 4 columnas

### Tarjeta de cada producto

```php
@forelse($products as $product)
<div class="bg-white rounded-lg shadow-md">

    {{-- Imagen o placeholder --}}
    @if($product->image)
        <img src="{{ asset('storage/' . $product->image) }}" ...>
    @else
        <div>Sin imagen</div>
    @endif

    {{-- Nombre y descripción --}}
    <h3>{{ $product->name }}</h3>
    <p>{{ Str::limit($product->description, 100) }}</p>

    {{-- Precio y stock --}}
    ${{ number_format($product->price, 2) }}
    {{ $product->stock > 0 ? 'Stock: ' . $product->stock : 'Agotado' }}

    {{-- Botones --}}
    <a href="{{ route('products.show', $product) }}">Ver detalles</a>

    @if($product->stock > 0)
    <form action="{{ route('cart.add', $product) }}" method="POST">
        @csrf
        <button>Agregar</button>
    </form>
    @endif

</div>
@empty
    <p>No hay productos disponibles</p>
@endforelse
```

### Puntos importantes

**`@forelse` / `@empty`**
Es como `@foreach` pero tiene un bloque `@empty` que se muestra si la lista está vacía. Evita tener que escribir un `@if` extra.

**`Str::limit($product->description, 100)`**
Corta la descripción a máximo 100 caracteres y agrega `...` al final. Evita que descripciones largas rompan el diseño de la tarjeta.

**`number_format($product->price, 2)`**
Formatea el número con dos decimales. Por ejemplo: `50000` → `50,000.00`.

**`asset('storage/' . $product->image)`**
Genera la URL completa de la imagen. Por ejemplo: `http://localhost:8000/storage/products/camisa.jpg`.

> ⚠️ Para que las imágenes funcionen se debe ejecutar:  
> `php artisan storage:link`

**El botón "Agregar" solo aparece si hay stock:**
```php
@if($product->stock > 0)
    {{-- botón agregar --}}
@endif
```

---

## Vista: `products/show.blade.php` — Detalle del producto

Muestra la información completa de un producto en un layout de dos columnas.

```php
<div class="md:grid md:grid-cols-2">
    {{-- Columna izquierda: imagen --}}
    <div>
        @if($product->image)
            <img src="{{ asset('storage/' . $product->image) }}" ...>
        @else
            <div>Sin imagen</div>
        @endif
    </div>

    {{-- Columna derecha: información --}}
    <div>
        <h1>{{ $product->name }}</h1>

        {{-- Disponibilidad --}}
        <p class="{{ $product->active ? 'text-green-600' : 'text-red-600' }}">
            {{ $product->active ? 'Disponible' : 'No disponible' }}
        </p>

        <p>{{ $product->description ?: 'Sin descripción.' }}</p>

        <p>${{ number_format($product->price, 2) }}</p>
        <p>{{ $product->stock > 0 ? 'Stock: ' . $product->stock : 'Agotado' }}</p>

        {{-- Botones --}}
        <a href="{{ route('products.index') }}">Volver</a>

        @if($product->active && $product->stock > 0)
        <form action="{{ route('cart.add', $product) }}" method="POST">
            @csrf
            <button>Agregar al carrito</button>
        </form>
        @endif
    </div>
</div>
```

### Operador ternario en Blade

Se usa mucho en esta vista para mostrar texto o color según condiciones:

```php
// Si activo → verde, si no → rojo
class="{{ $product->active ? 'text-green-600' : 'text-red-600' }}"

// Si hay stock → mostrar cantidad, si no → "Agotado"
{{ $product->stock > 0 ? 'Stock: ' . $product->stock : 'Agotado' }}

// Si hay descripción → mostrarla, si no → texto por defecto
{{ $product->description ?: 'Sin descripción.' }}
```

**El botón "Agregar al carrito" tiene doble condición:**
```php
@if($product->active && $product->stock > 0)
```
Solo aparece si el producto está activo **Y** tiene stock. Si cualquiera de las dos falla, el botón no se muestra.

---

## Vista: `cart/index.blade.php` — Carrito

Tiene dos estados: carrito vacío o carrito con productos.

### Estado vacío

```php
@if(empty($cart))
    {{-- Ícono de carrito vacío --}}
    <p>Tu carrito está vacío</p>
    <a href="{{ route('products.index') }}">Ver productos</a>
@else
    {{-- Lista de productos --}}
@endif
```

### Estado con productos

```php
@foreach($cart as $id => $item)
<div class="flex items-center py-4 border-b">

    {{-- Imagen --}}
    <img src="{{ asset('storage/' . $item['image']) }}" ...>

    {{-- Nombre y precio unitario --}}
    <h3>{{ $item['name'] }}</h3>
    <p>${{ number_format($item['price'], 2) }} c/u</p>

    {{-- Actualizar cantidad --}}
    <form action="{{ route('cart.update', $id) }}" method="POST">
        @csrf
        @method('PUT')                       ← simula método PUT
        <input type="number" name="quantity" value="{{ $item['quantity'] }}">
        <button>Actualizar</button>
    </form>

    {{-- Eliminar --}}
    <form action="{{ route('cart.remove', $id) }}" method="POST">
        @csrf
        @method('DELETE')                    ← simula método DELETE
        <button>Eliminar</button>
    </form>

    {{-- Subtotal --}}
    ${{ number_format($item['price'] * $item['quantity'], 2) }}

</div>
@endforeach
```

### ¿Qué es `@method('PUT')` y `@method('DELETE')`?

Los navegadores solo soportan `GET` y `POST` en formularios HTML. Para usar `PUT` y `DELETE` (que Laravel necesita para las rutas de actualizar y eliminar), Blade agrega un campo oculto:

```html
<!-- Lo que genera @method('PUT') -->
<input type="hidden" name="_method" value="PUT">
```

Laravel lee ese campo oculto y entiende que realmente es una petición `PUT`.

### Total y botones finales

```php
{{-- Total --}}
${{ number_format($total, 2) }}

{{-- Botones --}}
<a href="{{ route('products.index') }}">Seguir comprando</a>

<form action="{{ route('checkout.store') }}" method="POST">
    @csrf
    <button>Proceder al pago</button>
</form>
```

---

## Vista: `admin/products/_form.blade.php` — Formulario reutilizable

Este archivo es especial: **no es una página completa**, es solo el formulario de campos que se reutiliza tanto en crear como en editar un producto.

### ¿Por qué es reutilizable?

```php
// En create.blade.php:
<form action="{{ route('admin.products.store') }}" method="POST">
    @include('admin.products._form')   ← incluye el formulario
    <button>Crear producto</button>
</form>

// En edit.blade.php:
<form action="{{ route('admin.products.update', $product) }}" method="POST">
    @method('PUT')
    @include('admin.products._form')   ← mismo formulario
    <button>Actualizar producto</button>
</form>
```

El formulario es exactamente el mismo en los dos casos. Solo cambia la acción y el botón de envío.

### Detectar si es edición o creación

```php
@php
    $isEdit = isset($product);  // true si $product existe (edición)
@endphp
```

Esta variable se usa para mostrar la imagen actual solo cuando se está editando:

```php
@if($isEdit && $product->image)
    <p>Imagen actual:</p>
    <img src="{{ asset('storage/' . $product->image) }}" ...>
@endif
```

### Preservar valores con `old()`

```php
value="{{ old('name', $product->name ?? '') }}"
```

Esta expresión tiene dos comportamientos:
- Si hubo un error de validación: muestra el valor que el usuario ya había escrito (`old('name')`).
- Si es la primera vez que se abre el formulario: muestra el valor actual del producto (`$product->name`) o vacío si es creación (`?? ''`).

### El checkbox de "Producto activo"

```php
<input
    type="checkbox"
    name="active"
    value="1"
    {{ old('active', $product->active ?? true) ? 'checked' : '' }}
>
```

- En **creación**: el checkbox empieza marcado (`?? true`) porque los productos nuevos son activos por defecto.
- En **edición**: el checkbox refleja el estado actual del producto (`$product->active`).
- Si hubo error de validación: refleja lo que el usuario había seleccionado (`old('active')`).

---

## Resumen: ¿Dónde modificar cada cosa?

| ¿Qué quieres cambiar? | ¿Qué archivo editar? |
|---|---|
| El navbar o los estilos generales | `layouts/app.blade.php` |
| La grilla del catálogo de productos | `products/index.blade.php` |
| La página de detalle de un producto | `products/show.blade.php` |
| La página del carrito | `cart/index.blade.php` |
| Los campos del formulario de productos | `admin/products/_form.blade.php` |
| La lista de productos en el admin | `admin/products/index.blade.php` |
| El formulario de login del admin | `admin/login.blade.php` |

---

## Clases de Tailwind más usadas en el proyecto

| Clase | ¿Qué hace? |
|---|---|
| `bg-white` | Fondo blanco |
| `bg-gray-100` | Fondo gris claro |
| `text-blue-600` | Texto azul |
| `text-green-600` | Texto verde |
| `text-red-600` | Texto rojo |
| `font-bold` | Texto en negrita |
| `rounded-lg` | Bordes redondeados |
| `shadow-md` | Sombra mediana |
| `p-4` / `px-4` / `py-4` | Padding (espacio interno) |
| `m-4` / `mx-4` / `my-4` | Margin (espacio externo) |
| `flex` | Diseño flexible en fila |
| `grid` | Diseño en grilla |
| `grid-cols-4` | Grilla de 4 columnas |
| `gap-6` | Espacio entre elementos de la grilla |
| `max-w-7xl mx-auto` | Ancho máximo centrado en la página |
| `hover:bg-blue-700` | Color al pasar el mouse |
| `transition` | Animación suave al cambiar estilos |
