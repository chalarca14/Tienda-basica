# 🔐 Autenticación del Panel de Administración

> **Nivel:** Principiantes  
> **Proyecto:** Tienda Básica — Laravel 12  
> **Objetivo:** Explicar cómo se protegió el módulo `/admin` para que solo los administradores puedan editar productos.

---

## ¿Qué problema resolvimos?

Antes de estos cambios, cualquier persona que supiera la dirección `/admin/products` podía entrar al panel y editar, crear o eliminar productos sin necesidad de contraseña.

**Lo que necesitábamos:**
- Si alguien intenta entrar al panel → que le pida usuario y contraseña.
- Si las credenciales son correctas y es administrador → que lo deje entrar.
- Si no son correctas o no es administrador → que no lo deje pasar.

---

## Conceptos básicos

Antes de ver el código, es importante entender qué significa cada término.

### ¿Qué es una migración?
Un archivo de instrucciones que le dice a Laravel cómo modificar la base de datos. En lugar de hacer cambios a mano en phpMyAdmin, escribimos el cambio en código para que cualquier persona del equipo pueda reproducir la misma estructura con un solo comando.

> 💡 **Analogía:** Imagina que la base de datos es una hoja de Excel. Una migración es un instructivo escrito que dice: *"agrega una columna llamada is_admin en la tabla de usuarios"*. Así todos tienen el mismo Excel sin hacerlo a mano.

### ¿Qué es un Middleware?
Un filtro que se ejecuta automáticamente **antes** de que una página cargue. Es como un guardia de seguridad en la puerta de entrada.

> 💡 **Analogía:** El panel admin es una oficina privada. El middleware es el guardia en la puerta que pregunta: *¿tienes credenciales?* Si las tienes → te deja entrar. Si no → te manda al login.

### ¿Qué es una sesión?
Cuando inicias sesión, el servidor crea un espacio temporal donde guarda quién eres mientras navegas. Es como una pulsera de acceso en un evento: mientras la tengas, el guardia sabe que ya pasaste el control.

### ¿Qué es el campo `is_admin`?
Una columna en la tabla de usuarios que solo puede tener dos valores:
- `false` → usuario normal, no puede entrar al panel.
- `true` → administrador, tiene acceso al panel.

### ¿Qué es el hash de contraseña?
Las contraseñas nunca se guardan tal como las escribe el usuario. Se encriptan con un algoritmo llamado **bcrypt**. Aunque alguien robe la base de datos, no puede saber la contraseña original.

> 💡 **Analogía:** Es como meter un mensaje en una caja fuerte y destruir la llave. Solo puedes verificar si una contraseña es correcta, pero no puedes leerla.

### ¿Qué es CSRF?
Un tipo de ataque donde un sitio malicioso intenta enviar formularios en tu nombre. Laravel lo previene generando un token secreto (`@csrf`) en cada formulario. Sin ese token, Laravel rechaza la petición.

---

## Archivos creados o modificados

| Archivo | Acción | Propósito |
|---|---|---|
| `database/migrations/...add_is_admin.php` | CREADO | Agrega columna `is_admin` a users |
| `app/Models/User.php` | MODIFICADO | Cast booleano + método `isAdmin()` |
| `app/Http/Middleware/IsAdmin.php` | CREADO | Filtro de seguridad para `/admin` |
| `bootstrap/app.php` | MODIFICADO | Registra el alias `is_admin` |
| `routes/web.php` | MODIFICADO | Rutas públicas y protegidas |
| `app/Http/Controllers/Admin/AuthController.php` | CREADO | Login y logout del admin |
| `resources/views/admin/login.blade.php` | CREADO | Formulario de acceso |
| `database/seeders/AdminUserSeeder.php` | CREADO | Crea el usuario admin inicial |
| `app/Http/Controllers/Admin/AdminProductController.php` | MOVIDO | Reubicado a la carpeta `Admin/` |

---

## Paso 1 — Agregar el campo `is_admin` a la base de datos

La tabla `users` ya existía pero no tenía forma de distinguir administradores de usuarios normales. Se creó una migración para agregar ese campo.

**Crear el archivo de migración:**
```bash
php artisan make:migration add_is_admin_to_users_table --table=users
```

**Código de la migración** (`database/migrations/xxxx_add_is_admin_to_users_table.php`):
```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_admin')  // columna de tipo verdadero/falso
              ->default(false)       // por defecto todo usuario es normal
              ->after('password');   // ubicarla después de la columna password
    });
}

public function down(): void
{
    // down() permite deshacer el cambio si algo sale mal
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('is_admin');
    });
}
```

**Aplicar el cambio en la base de datos:**
```bash
php artisan migrate
```

> ✅ **Resultado:** La tabla `users` ahora tiene la columna `is_admin`. Todos los usuarios existentes quedaron con `is_admin = false`. Solo el usuario administrador que creamos después tiene `is_admin = true`.

---

## Paso 2 — Actualizar el modelo `User.php`

El modelo es el archivo PHP que representa la tabla `users`. Se actualizó para reconocer el nuevo campo y agregar un método helper.

```php
// app/Models/User.php

protected $fillable = [
    'name',
    'email',
    'password',
    'is_admin',   // ← permite guardar este campo con create()
];

protected $casts = [
    'email_verified_at' => 'datetime',
    'password'          => 'hashed',
    'is_admin'          => 'boolean',  // ← convierte 1/0 a true/false automáticamente
];

// Método helper para preguntar si el usuario es admin
public function isAdmin(): bool
{
    return $this->is_admin === true;
}
```

| Qué agregamos | Para qué sirve |
|---|---|
| `'is_admin'` en `$fillable` | Permite asignar el campo al crear o actualizar un usuario. Sin esto Laravel lo ignoraría. |
| `'is_admin' => 'boolean'` en `$casts` | La BD guarda `1` o `0`. Con este cast Laravel lo convierte a `true` o `false` en PHP. |
| Método `isAdmin()` | Forma sencilla de verificar el rol desde cualquier parte del código. |

---

## Paso 3 — Crear el Middleware (el guardia de seguridad)

El middleware se ejecuta automáticamente cada vez que alguien intenta entrar a cualquier página de `/admin`.

**Crear el archivo:**
```bash
php artisan make:middleware IsAdmin
```

**Código** (`app/Http/Middleware/IsAdmin.php`):
```php
public function handle(Request $request, Closure $next)
{
    // PREGUNTA 1: ¿Hay alguien con sesión activa?
    if (!Auth::check()) {
        return redirect()->route('admin.login')
                         ->withErrors(['acceso' => 'Debes iniciar sesión primero.']);
    }

    // PREGUNTA 2: El usuario logueado, ¿es administrador?
    if (!Auth::user()->isAdmin()) {
        Auth::logout(); // cerrar sesión del usuario normal
        return redirect()->route('admin.login')
                         ->withErrors(['acceso' => 'No tienes permisos de administrador.']);
    }

    // Pasó las dos preguntas → dejar entrar
    return $next($request);
}
```

**¿Cómo funciona?**

```
Alguien entra a /admin/products
          ↓
¿Tiene sesión activa?
   NO → redirige a /admin/login
   SÍ ↓
¿Es administrador?
   NO → logout + redirige a /admin/login
   SÍ → deja entrar al panel ✅
```

> ⚠️ **¿Por qué se hace `logout` si no es admin?**  
> Para limpiar la sesión de un usuario normal que intentó acceder al admin y evitar estados inconsistentes.

---

## Paso 4 — Registrar el Middleware con un nombre

Para poder usar el middleware en las rutas con un nombre corto, se registra en `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'is_admin' => \App\Http\Middleware\IsAdmin::class,
    ]);
})
```

> 💡 Sin el alias habría que escribir la ruta completa de la clase en cada ruta. Con el alias solo se escribe `->middleware('is_admin')`.

---

## Paso 5 — Organizar las rutas en `web.php`

Las rutas del panel admin se separaron en dos grupos:

```php
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AuthController;

// ── GRUPO 1: Rutas PÚBLICAS (sin middleware) ──
// Son la puerta de entrada. Si estuvieran protegidas, nadie podría entrar jamás.
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',  [AuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ── GRUPO 2: Rutas PROTEGIDAS (con middleware is_admin) ──
Route::prefix('admin')->name('admin.')->middleware('is_admin')->group(function () {
    Route::resource('products', AdminProductController::class)->except(['show']);
});
```

| Parte del código | ¿Qué hace? |
|---|---|
| `prefix('admin')` | Todas las rutas empiezan con `/admin/`. Evita repetirlo en cada ruta. |
| `name('admin.')` | Da nombres a las rutas: `admin.login`, `admin.products.index`, etc. |
| `->middleware('is_admin')` | Aplica el filtro a todo el grupo protegido. |
| `Route::resource(...)` | Genera las 7 rutas estándar: listar, crear, guardar, editar, actualizar y eliminar. |
| `->except(['show'])` | Excluye la ruta de detalle individual que no se necesita en el admin. |

> ⚠️ **¿Por qué el login NO tiene middleware?**  
> Si el login estuviera protegido, el middleware redirigiría al login, que redirigiría al login, creando un bucle infinito. El login siempre debe ser público.

---

## Paso 6 — Crear el `AuthController`

Maneja el login y logout del administrador. Va en `app/Http/Controllers/Admin/AuthController.php`.

### `showLogin()` — Mostrar el formulario
```php
public function showLogin()
{
    // Si ya hay sesión activa de admin, ir directo al panel
    if (Auth::check() && Auth::user()->isAdmin()) {
        return redirect()->route('admin.products.index');
    }
    return view('admin.login');
}
```

### `login()` — Procesar el formulario
```php
public function login(Request $request)
{
    // Paso 1: Verificar que los campos no estén vacíos
    $request->validate([
        'email'    => ['required', 'email'],
        'password' => ['required'],
    ]);

    // Paso 2: Intentar autenticar con email y contraseña
    if (Auth::attempt($request->only('email', 'password'))) {

        // Paso 3: ¿Es administrador?
        if (!Auth::user()->isAdmin()) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Esta cuenta no tiene permisos de administrador.',
            ]);
        }

        // Paso 4: Regenerar sesión por seguridad y entrar al panel
        $request->session()->regenerate();
        return redirect()->route('admin.products.index');
    }

    // Paso 5: Credenciales incorrectas
    return back()->withErrors([
        'email' => 'Correo o contraseña incorrectos.',
    ])->onlyInput('email');
}
```

| Paso | ¿Por qué es necesario? |
|---|---|
| Validar campos | Si el email está vacío, no tiene sentido consultar la base de datos. |
| `Auth::attempt()` | Laravel busca el usuario y compara la contraseña con el hash en la BD. |
| Verificar `isAdmin()` | `attempt()` autentica a cualquier usuario, no solo admins. Se necesita esta verificación extra. |
| `session()->regenerate()` | Cambia el ID de sesión para prevenir ataques de robo de sesión. |
| Error genérico | No se indica si el email existe o no, para no darle pistas a un atacante. |

### `logout()` — Cerrar sesión
```php
public function logout(Request $request)
{
    Auth::logout();                          // 1. Desautentica al usuario
    $request->session()->invalidate();       // 2. Destruye todos los datos de sesión
    $request->session()->regenerateToken();  // 3. Invalida el token CSRF
    return redirect()->route('admin.login'); // 4. Lleva al login
}
```

> ⚠️ Son necesarios los tres pasos porque `logout()` solo desautentica en memoria, `invalidate()` borra los datos de sesión, y `regenerateToken()` invalida formularios abiertos anteriormente.

---

## Paso 7 — Crear la vista de login

La vista del formulario va en `resources/views/admin/login.blade.php`.

```php
<form method="POST" action="{{ route('admin.login.post') }}">

    {{-- Token de seguridad obligatorio en todo formulario POST --}}
    @csrf

    {{-- Mostrar errores si el login falló --}}
    @if ($errors->any())
        <div class="error">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- old('email') recupera el email escrito si hubo error --}}
    <input type="email" name="email" value="{{ old('email') }}" required>

    <input type="password" name="password" required>

    <button type="submit">Iniciar sesión</button>
</form>
```

| Elemento | ¿Qué hace? |
|---|---|
| `@csrf` | Genera un token secreto. Sin él Laravel rechaza el formulario con error 419. |
| `method="POST"` | Las credenciales van por POST, no por GET, para que no aparezcan en la URL. |
| `$errors->any()` | Variable de Laravel con los errores enviados por el controlador con `withErrors()`. |
| `old('email')` | Recupera el email escrito si el usuario regresó al formulario por un error. |

---

## Paso 8 — Crear el usuario administrador (Seeder)

Sin un usuario con `is_admin = true` nadie podría entrar al panel. El seeder crea ese usuario inicial.

```bash
php artisan make:seeder AdminUserSeeder
```

```php
// database/seeders/AdminUserSeeder.php

public function run(): void
{
    User::updateOrCreate(
        ['email' => 'admin@tienda.com'],   // buscar por este email
        [
            'name'     => 'Administrador',
            'email'    => 'admin@tienda.com',
            'password' => Hash::make('admin123'),  // encripta la contraseña
            'is_admin' => true,
        ]
    );
}
```

```bash
php artisan db:seed --class=AdminUserSeeder
```

| Parte | ¿Qué hace? |
|---|---|
| `updateOrCreate()` | Si el usuario ya existe lo actualiza. Si no, lo crea. Evita duplicados al correr el seeder varias veces. |
| `Hash::make()` | Encripta la contraseña. Nunca se guarda en texto plano en la base de datos. |
| `'is_admin' => true` | Marca este usuario como administrador. Solo así pasará el middleware. |

> ⚠️ **Credenciales iniciales:** `admin@tienda.com` / `admin123`  
> Cambiar antes de publicar el sitio en producción.

---

## Problema encontrado y solucionado

Durante la implementación apareció este error:

```
Target class [App\Http\Controllers\Admin\AdminProductController] does not exist.
```

**¿Qué pasó?**  
El archivo `AdminProductController.php` estaba en `Controllers/` en lugar de `Controllers/Admin/`. En Laravel, la ubicación del archivo en carpetas debe coincidir exactamente con el namespace escrito dentro del archivo.

| | Incorrecto ❌ | Correcto ✅ |
|---|---|---|
| Ubicación del archivo | `Controllers/AdminProductController.php` | `Controllers/Admin/AdminProductController.php` |
| Namespace en el archivo | `App\Http\Controllers` | `App\Http\Controllers\Admin` |

**Solución:**
```bash
# Mover el archivo a la carpeta correcta
mv app/Http/Controllers/AdminProductController.php \
   app/Http/Controllers/Admin/AdminProductController.php

# Regenerar el mapa de clases
composer dump-autoload

# Limpiar cachés
php artisan route:clear
php artisan cache:clear
```

> 💡 `composer dump-autoload` regenera la lista interna de clases de Laravel. Es necesario ejecutarlo cada vez que se mueve o crea un archivo nuevo.

---

## Flujo completo del sistema

### Escenario 1 — Usuario sin sesión intenta entrar
1. Escribe `/admin/products` en el navegador.
2. El Middleware `IsAdmin` se ejecuta automáticamente.
3. Middleware: ¿hay sesión? → **NO** → redirige a `/admin/login`.

### Escenario 2 — Contraseña incorrecta
1. Escribe email y contraseña en el formulario.
2. `Auth::attempt()` no encuentra coincidencia en la BD.
3. Muestra: *"Correo o contraseña incorrectos."*

### Escenario 3 — Usuario normal intenta entrar
1. Escribe credenciales correctas de un usuario normal.
2. `Auth::attempt()` autentica correctamente.
3. `isAdmin()` devuelve `false` → se hace logout.
4. Muestra: *"Esta cuenta no tiene permisos de administrador."*

### Escenario 4 — Administrador entra correctamente ✅
1. Escribe credenciales del administrador.
2. `Auth::attempt()` autentica correctamente.
3. `isAdmin()` devuelve `true` → se regenera la sesión.
4. El admin llega al panel `/admin/products`.

### Escenario 5 — Admin cierra sesión
1. Da clic en "Cerrar sesión".
2. Se ejecuta `logout()`: destruye sesión y token CSRF.
3. Redirige a `/admin/login`.

---

## Glosario

| Término | Definición simple |
|---|---|
| Migración | Archivo de instrucciones que modifica la estructura de la base de datos. |
| Middleware | Filtro que se ejecuta antes de que una página cargue. Como un guardia en la puerta. |
| Sesión | Espacio temporal donde el servidor recuerda quién eres mientras navegas. |
| `is_admin` | Campo en la BD que indica si un usuario es administrador (`true`) o no (`false`). |
| Hash / bcrypt | Algoritmo que encripta contraseñas de forma que no se pueden descifrar. |
| CSRF | Tipo de ataque web. El `@csrf` en formularios lo previene. |
| Namespace | La "dirección" de un archivo dentro del proyecto. Debe coincidir con su carpeta. |
| Seeder | Archivo que inserta datos iniciales en la base de datos. |
| Controlador | Archivo PHP que recibe las acciones del usuario y decide qué hacer. |
| Modelo | Archivo PHP que representa una tabla de la base de datos. |
| Ruta (Route) | Asocia una URL con el código que debe ejecutarse. |
| `Auth::attempt()` | Función de Laravel que verifica email y contraseña contra la base de datos. |
| `dump-autoload` | Comando que regenera el mapa de clases después de mover o crear archivos. |
| Blade | Motor de plantillas de Laravel para mezclar PHP con HTML usando `@directivas`. |
| `fillable` | Lista de campos que se pueden guardar masivamente con `create()` en un modelo. |
