# 🛒 Carrito de Compras

> Aquí se explica cómo funciona el carrito de compras del proyecto.

---

## ¿Cómo funciona el carrito?

El carrito se guarda en la **sesión** del navegador, no en la base de datos. Esto significa que es temporal: existe mientras el usuario navega por la tienda, pero se borra cuando cierra el navegador o termina la sesión.

> 💡 **¿Qué es una sesión?**  
> Es un espacio de memoria temporal que el servidor le asigna a cada visitante. Es como una canasta física en el supermercado: existe mientras estás en la tienda, pero al salir la dejas.

### Estructura del carrito en sesión

El carrito se guarda como un array asociativo donde la **clave** es el ID del producto:

```php
// Así luce el carrito en sesión:
[
    5 => [                        // ← ID del producto
        'name'     => 'Camisa',
        'price'    => 50000,
        'quantity' => 2,
        'image'    => 'products/camisa.jpg',
    ],
    12 => [
        'name'     => 'Pantalón',
        'price'    => 80000,
        'quantity' => 1,
        'image'    => 'products/pantalon.jpg',
    ],
]
```

---

## Archivo: `CartController.php`

Este controlador maneja todas las acciones del carrito. Tiene 5 métodos públicos y 2 privados.

---

## Método `index()` — Ver el carrito

```php
public function index()
{
    $cart = $this->syncCartWithInventory(session()->get('cart', []));
    session()->put('cart', $cart);
    $total = $this->calculateTotal($cart);
    return view('cart.index', compact('cart', 'total'));
}
```

### ¿Qué hace paso a paso?

1. **`session()->get('cart', [])`** — Lee el carrito de la sesión. Si no existe, devuelve un array vacío.
2. **`syncCartWithInventory()`** — Sincroniza el carrito con el inventario actual (explicado abajo).
3. **`session()->put('cart', $cart)`** — Guarda el carrito sincronizado de vuelta en la sesión.
4. **`calculateTotal()`** — Calcula el total sumando precio × cantidad de cada item.
5. **`return view(...)`** — Muestra la página del carrito con los datos.

> ⚠️ **¿Por qué sincronizar antes de mostrar el carrito?**  
> Porque entre que el usuario agregó un producto y abre el carrito, ese producto pudo haberse agotado o desactivado. La sincronización actualiza el carrito con el estado real del inventario.

---

## Método `add()` — Agregar producto al carrito

```php
public function add(Request $request, Product $product)
{
    // Validación 1: ¿El producto está activo?
    if (!$product->active) {
        return redirect()->back()->with('error', 'Este producto no está disponible.');
    }

    // Validación 2: ¿Tiene stock?
    if ($product->stock < 1) {
        return redirect()->back()->with('error', 'Este producto no tiene stock disponible.');
    }

    $cart = session()->get('cart', []);
    $currentQuantity = $cart[$product->id]['quantity'] ?? 0;

    // Validación 3: ¿La cantidad en carrito no supera el stock?
    if ($currentQuantity >= $product->stock) {
        return redirect()->back()->with('error', 'No puedes agregar más unidades que el stock disponible.');
    }

    // Agregar o incrementar en el carrito
    $cart[$product->id] = [
        'name'     => $product->name,
        'price'    => $product->price,
        'quantity' => $currentQuantity + 1,
        'image'    => $product->image,
    ];

    session()->put('cart', $cart);
    return redirect()->back()->with('success', 'Producto agregado al carrito.');
}
```

### Las 3 validaciones en orden

| Validación | ¿Por qué en ese orden? |
|---|---|
| ¿Está activo? | Es la condición más fundamental. Si no está activo, no existe para la venta. |
| ¿Tiene stock? | Solo tiene sentido si el producto está activo. |
| ¿La cantidad no supera el stock? | Evita que el carrito tenga más unidades de las disponibles. |

> 💡 Este patrón se llama **"fail fast"** (fallar rápido): verificar las condiciones más básicas primero para no hacer trabajo innecesario.

---

## Método `update()` — Actualizar cantidad

```php
public function update(Request $request, $id)
{
    // Validar que la cantidad sea un número entero positivo
    $validated = $request->validate([
        'quantity' => ['required', 'integer', 'min:1'],
    ]);

    $cart = session()->get('cart', []);

    // ¿El producto está en el carrito?
    if (!isset($cart[$id])) {
        return redirect()->route('cart.index')->with('error', 'El producto no existe en el carrito.');
    }

    $product = Product::find($id);

    // ¿El producto sigue siendo válido?
    if (!$product || !$product->active || $product->stock < 1) {
        unset($cart[$id]);                    // ← eliminar del carrito
        session()->put('cart', $cart);
        return redirect()->route('cart.index')->with('error', 'El producto ya no está disponible y fue retirado del carrito.');
    }

    // ¿La nueva cantidad no supera el stock?
    if ($validated['quantity'] > $product->stock) {
        return redirect()->route('cart.index')->with('error', 'Solo hay ' . $product->stock . ' unidades disponibles.');
    }

    // Actualizar el carrito con los datos más recientes del producto
    $cart[$id] = [
        'name'     => $product->name,
        'price'    => $product->price,
        'quantity' => $validated['quantity'],
        'image'    => $product->image,
    ];

    session()->put('cart', $cart);
    return redirect()->route('cart.index')->with('success', 'Cantidad actualizada.');
}
```

### Puntos importantes

- Si el producto fue desactivado o eliminado mientras estaba en el carrito, se **elimina automáticamente** del carrito con un mensaje al usuario.
- Se actualizan también el nombre, precio e imagen del producto para que el carrito siempre tenga información fresca.

---

## Método `remove()` — Eliminar producto del carrito

```php
public function remove($id)
{
    $cart = session()->get('cart', []);

    if (isset($cart[$id])) {
        unset($cart[$id]);
        session()->put('cart', $cart);
    }

    return redirect()->route('cart.index');
}
```

El método más simple: si el producto existe en el carrito, lo elimina. Si no existe, no hace nada (evita errores).

---

## Método privado `calculateTotal()` — Calcular el total

```php
private function calculateTotal($cart)
{
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}
```

Recorre todos los items del carrito y suma `precio × cantidad` de cada uno.

> ⚠️ **Importante:** El precio se toma de la sesión (que viene de la BD), **nunca** del formulario enviado por el usuario. Esto evita que alguien manipule el precio desde el navegador.

---

## Método privado `syncCartWithInventory()` — Sincronizar con inventario

```php
private function syncCartWithInventory(array $cart): array
{
    if (empty($cart)) {
        return [];
    }

    // Obtener todos los productos del carrito en una sola consulta
    $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');

    $syncedCart = [];
    foreach ($cart as $id => $item) {
        $product = $products->get((int) $id);

        // Si el producto no existe, está inactivo o sin stock → eliminarlo
        if (!$product || !$product->active || $product->stock < 1) {
            continue; // ← saltar este producto (no agregarlo al carrito limpio)
        }

        // Ajustar la cantidad al rango válido [1, stock_disponible]
        $quantity = max(1, min((int) $item['quantity'], $product->stock));

        $syncedCart[$id] = [
            'name'     => $product->name,
            'price'    => $product->price,
            'quantity' => $quantity,
            'image'    => $product->image,
        ];
    }

    return $syncedCart;
}
```

### ¿Qué hace exactamente?

1. Obtiene todos los productos del carrito en **una sola consulta** a la BD (eficiente).
2. Por cada producto en el carrito verifica:
   - ¿Sigue existiendo? ¿Está activo? ¿Tiene stock? → Si no cumple alguna, lo elimina.
3. Ajusta la cantidad con `max(1, min(cantidad, stock))`:

```
Ejemplo:
  Carrito tiene: quantity = 8
  Stock actual:  3

  min(8, 3) = 3   → no puede tener más que el stock
  max(1, 3) = 3   → no puede tener menos de 1

  Resultado: quantity = 3 ✅

Caso extremo:
  Carrito tiene: quantity = 0 (dato corrupto)
  Stock actual:  5

  min(0, 5) = 0   
  max(1, 0) = 1   → mínimo 1 para que el producto permanezca en el carrito
```

---

## Flujo completo del carrito

```
Cliente ve un producto
        ↓
Da clic en "Agregar al carrito"
        ↓
CartController::add()
  ¿Producto activo?   NO → mensaje de error
  ¿Tiene stock?       NO → mensaje de error
  ¿Cantidad OK?       NO → mensaje de error
  Todo OK → guardar en sesión
        ↓
Cliente ve su carrito (CartController::index())
  syncCartWithInventory() → limpiar items inválidos
  calculateTotal() → sumar precios
  Mostrar vista
        ↓
Cliente puede:
  - Cambiar cantidad → update()
  - Eliminar producto → remove()
  - Proceder al pago → CheckoutController::store()
```

---

## Rutas del carrito

```php
Route::get('/cart',                [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/update/{id}',    [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
```

| Ruta | Método HTTP | ¿Qué hace? |
|---|---|---|
| `/cart` | GET | Muestra el carrito |
| `/cart/add/{id}` | POST | Agrega un producto |
| `/cart/update/{id}` | PUT | Actualiza la cantidad |
| `/cart/remove/{id}` | DELETE | Elimina un producto |
