<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-
scale=1.0">

    <title>@yield('title', 'Mi Tienda')</title>
    {{-- Tailwind CSS (recomendado) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    {{-- O CSS personalizado --}}
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @if (file_exists(public_path('build/manifest.json')) ||
    file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body class="bg-gray-100">
    {{-- Navbar --}}
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex space-x-4">
                    <a href="{{ route('home') }}" class="font-bold text-xl">Mi
                        Tienda</a>

                    <a href="{{ route('products.index') }}" class="hover:text-gray-
600">Productos</a>

                    <a href="{{ route('admin.products.index') }}" class="hover:text-
gray-600">Administrar</a>

                </div>
                {{-- Carrito --}}
                <a href="{{ route('cart.index') }}" class="relative">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"

                            stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-
2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0

000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    @if(session()->has('cart') && count(session('cart')) > 0)

                    <span class="absolute -top-2 -right-2 bg-red-500 text-white
rounded-full w-5 h-5 flex items-center justify-center text-xs">
                        {{ count(session('cart')) }}
                    </span>
                    @endif
                </a>
            </div>
        </div>
    </nav>
    {{-- Mensajes flash --}}
    @if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700
px-4 py-3 rounded relative m-4" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3
rounded relative m-4" role="alert">
        <span class="block sm:inline">{{ session('error') }}</span>
    </div>
    @endif
    @if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3
rounded relative m-4" role="alert">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    {{-- Contenido principal --}}
    <main class="py-8">

        @yield('content')
    </main>
</body>

</html>