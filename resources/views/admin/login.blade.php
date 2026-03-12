{{-- resources/views/admin/login.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración — Login</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: white;
            padding: 2.5rem;
            border-radius: 14px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.10);
            width: 100%;
            max-width: 420px;
        }
        .logo {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        h1 {
            font-size: 1.4rem;
            color: #1a2f5e;
            text-align: center;
            margin-bottom: 0.3rem;
        }
        .subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.88rem;
            margin-bottom: 2rem;
        }
        .form-group { margin-bottom: 1.2rem; }
        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus { border-color: #2563eb; }
        .input-error { border-color: #ef4444 !important; }
        .btn {
            width: 100%;
            padding: 0.8rem;
            background: #1a2f5e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s;
        }
        .btn:hover { background: #2563eb; }
        .error-box {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 0.8rem 1rem;
            margin-bottom: 1.2rem;
            color: #b91c1c;
            font-size: 0.875rem;
        }
        .error-box p { margin-bottom: 0.2rem; }
        .error-box p:last-child { margin-bottom: 0; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">🔐</div>
    <h1>Panel de Administración</h1>
    <p class="subtitle">Solo administradores pueden acceder</p>

    @if ($errors->any())
        <div class="error-box">
            @foreach ($errors->all() as $error)
                <p>⚠ {{ $error }}</p>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.post') }}">
        @csrf

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="admin@tienda.com"
                class="{{ $errors->has('email') ? 'input-error' : '' }}"
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                class="{{ $errors->has('password') ? 'input-error' : '' }}"
                required
            >
        </div>

        <button type="submit" class="btn">Iniciar sesión →</button>
    </form>
</div>
</body>
</html>