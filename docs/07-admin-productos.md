# ⚙️ Panel de Administración de Productos

> Aquí se explica cómo el administrador puede gestionar los productos de la tienda.

---

## ¿Qué puede hacer el administrador?

Desde el panel de administración, el administrador puede:

- Ver la lista de todos los productos (activos e inactivos)
- Crear un producto nuevo
- Editar un producto existente
- Eliminar un producto
- Activar o desactivar un producto

> ⚠️ Para acceder al panel, el usuario debe estar autenticado como administrador. Si no lo está, será redirigido al login. Ver [08-autenticacion-admin.md](08-autenticacion-admin.md).

---

## Archivo: `AdminProductController.php`

Este controlador maneja todas las acciones del panel admin. Está en `app/Http/Controllers/Admin/AdminProductController.php`.

Tiene **6 métodos públicos** y **1 método privado**.

---

## Método `index()` — Listar productos

```php
public function index()
{
    $products = Product::latest()->paginate(10);
    return view('admin.products.index', compact('products'));
}
```

### ¿Qué hace?

- **`Product::latest()`** — Ordena los productos del más reciente al más antiguo (por `created_at`).
- **`->paginate(10)`** — Muestra 10 productos por página.
- A diferencia del catálogo público, aquí se muestran **todos** los productos, incluyendo los inactivos.

> 💡 En el catálogo público solo se ven productos activos (`where('active', true)`). En el admin se ven todos porque el administrador necesita poder gestionar también los inactivos.

---

## Método `create()` — Mostrar formulario de creación

```php
public function create()
{
    return view('admin.products.create');
}
```

Solo muestra la vista con el formulario vacío para crear un producto nuevo.

---

## Método `store()` — Guardar producto nuevo

```php
public function store(Request $request)
{
    $data = $this->validateProduct($request);

    if ($request->hasFile('image')) {
        $data['image'] = $request->file('image')->store('products', 'public');
    }

    Product::create($data);

    return redirect()->route('admin.products.index')
                     ->with('success', 'Producto creado correctamente.');
}
```

### ¿Qué hace paso a paso?

1. **`validateProduct($request)`** — Valida los datos del formulario (método privado, explicado abajo).
2. **`$request->hasFile('image')`** — Verifica si el usuario subió una imagen.
3. **`->store('products', 'public')`** — Guarda la imagen en `storage/app/public/products/` y devuelve la ruta relativa.
4. **`Product::create($data)`** — Crea el producto en la base de datos.
5. Redirige al listado con un mensaje de éxito.

---

## Método `edit()` — Mostrar formulario de edición

```php
public function edit(Product $product)
{
    return view('admin.products.edit', compact('product'));
}
```

Muestra el formulario de edición con los datos actuales del producto precargados. Usa Route Model Binding igual que el `ProductController`.

---

## Método `update()` — Guardar cambios

```php
public function update(Request $request, Product $product)
{
    $data = $this->validateProduct($request);

    if ($request->hasFile('image')) {
        // Eliminar la imagen anterior para no dejar archivos huérfanos
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $data['image'] = $request->file('image')->store('products', 'public');
    }

    $product->update($data);

    return redirect()->route('admin.products.index')
                     ->with('success', 'Producto actualizado correctamente.');
}
```

### Punto importante: eliminar la imagen anterior

```php
if ($product->image) {
    Storage::disk('public')->delete($product->image);
}
```

Antes de guardar la nueva imagen, se elimina la anterior del almacenamiento. Si no se hiciera esto, con el tiempo se acumularían cientos de imágenes antiguas que ya nadie usa, desperdiciando espacio en disco.

---

## Método `destroy()` — Eliminar producto

```php
public function destroy(Product $product)
{
    if ($product->image) {
        Storage::disk('public')->delete($product->image);
    }

    $product->delete();

    return redirect()->route('admin.products.index')
                     ->with('success', 'Producto eliminado correctamente.');
}
```

### ¿Qué hace?

1. Si el producto tiene imagen, la elimina del almacenamiento primero.
2. Elimina el producto de la base de datos.
3. Redirige al listado con mensaje de éxito.

> ⚠️ **Consideración:** Si el producto tiene órdenes asociadas, eliminarlo dejará `product_id = NULL` en los `order_items`, pero el historial se preserva gracias a que `product_name` y `unit_price` están guardados directamente en esa tabla.

---

## Método privado `validateProduct()` — Validar formulario

```php
private function validateProduct(Request $request): array
{
    $data = $request->validate([
        'name'        => ['required', 'string', 'max:255'],
        'description' => ['nullable', 'string'],
        'price'       => ['required', 'numeric', 'min:0'],
        'stock'       => ['required', 'integer', 'min:0'],
        'image'       => ['nullable', 'image', 'max:2048'],
    ]);

    $data['active'] = $request->boolean('active');

    return $data;
}
```

### ¿Por qué es un método privado y no está en `store()` y `update()` por separado?

Porque las reglas de validación son las mismas para crear y para editar. Si estuvieran duplicadas en los dos métodos, al querer cambiar una regla habría que hacerlo en dos lugares. Con el método privado se cambia en uno solo.

> 💡 Esto es el principio **DRY** (Don't Repeat Yourself — No te repitas).

### Reglas de validación

| Campo | Reglas | ¿Qué significan? |
|---|---|---|
| `name` | `required, string, max:255` | Obligatorio, texto, máximo 255 caracteres |
| `description` | `nullable, string` | Opcional, pero si se envía debe ser texto |
| `price` | `required, numeric, min:0` | Obligatorio, número, no puede ser negativo |
| `stock` | `required, integer, min:0` | Obligatorio, número entero, no puede ser negativo |
| `image` | `nullable, image, max:2048` | Opcional, debe ser imagen, máximo 2MB |

### El campo `active` y los checkboxes

```php
$data['active'] = $request->boolean('active');
```

Los checkboxes en HTML tienen un comportamiento especial: cuando están **desmarcados**, no envían ningún valor en el formulario. Solo envían algo cuando están marcados.

Si se usara `$request->input('active')`:
- Marcado → devuelve `'on'` o `'1'`
- Desmarcado → devuelve `null`

Con `$request->boolean('active')`:
- Marcado → devuelve `true`
- Desmarcado → devuelve `false` (nunca `null`)

---

## Rutas del panel admin

```php
Route::prefix('admin')->name('admin.')->middleware('is_admin')->group(function () {
    Route::resource('products', AdminProductController::class)->except(['show']);
});
```

`Route::resource` genera automáticamente estas rutas:

| Ruta | Método HTTP | Método del controlador | ¿Qué hace? |
|---|---|---|---|
| `/admin/products` | GET | `index()` | Lista todos los productos |
| `/admin/products/create` | GET | `create()` | Muestra formulario de creación |
| `/admin/products` | POST | `store()` | Guarda el producto nuevo |
| `/admin/products/{id}/edit` | GET | `edit()` | Muestra formulario de edición |
| `/admin/products/{id}` | PUT/PATCH | `update()` | Guarda los cambios |
| `/admin/products/{id}` | DELETE | `destroy()` | Elimina el producto |

> La ruta `show` está excluida con `->except(['show'])` porque en el admin no se necesita ver el detalle individual de un producto.

---

## Flujo completo de crear un producto

```
Admin va a /admin/products/create
        ↓
AdminProductController::create()
  Muestra formulario vacío
        ↓
Admin llena el formulario y da clic en "Guardar"
        ↓
AdminProductController::store()
  validateProduct() → ¿datos válidos?
    NO → regresa al formulario con errores
    SÍ ↓
  ¿Subió imagen?
    SÍ → guardar imagen en storage/
    NO → continuar sin imagen
  Product::create($data) → guardar en BD
        ↓
Redirigir a /admin/products con mensaje de éxito
```
