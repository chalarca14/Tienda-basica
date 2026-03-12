# 💳 Proceso de Pago (Checkout)

> Aquí se explica cómo funciona el proceso de compra cuando el cliente decide pagar.

---

## ¿Qué es el checkout?

El checkout es el proceso que convierte el carrito de compras en una orden real. Es el momento más crítico del sistema porque involucra varias operaciones que deben completarse todas juntas o no completarse ninguna.

---

## Archivo: `CheckoutController.php`

Solo tiene un método: `store()`. Se ejecuta cuando el cliente da clic en el botón de pagar.

```php
public function store(): RedirectResponse
{
    // FASE 1: Validar el carrito
    $cart = session()->get('cart', []);
    if (empty($cart)) {
        return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
    }

    $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
    foreach ($cart as $productId => $item) {
        $product = $products->get((int) $productId);
        $requestedQuantity = (int) ($item['quantity'] ?? 0);
        if (!$product || !$product->active || $product->stock < $requestedQuantity || $requestedQuantity < 1) {
            return redirect()->route('cart.index')->with('error', 'El carrito contiene productos sin disponibilidad. Actualiza tu carrito.');
        }
    }

    // FASE 2: Procesar la compra dentro de una transacción
    try {
        DB::transaction(function () use ($cart): void {

            // Calcular el total
            $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
            $total = 0;
            foreach ($cart as $productId => $item) {
                $product = $products->get((int) $productId);
                $total += $product->price * (int) $item['quantity'];
            }

            // Crear la orden
            $order = Order::create([
                'user_id'  => auth()->id(),
                'total'    => $total,
                'status'   => 'paid',
                'placed_at'=> now(),
            ]);

            // Crear los items y descontar stock
            foreach ($cart as $productId => $item) {
                $product = Product::findOrFail($productId);
                $quantity = (int) $item['quantity'];

                // Verificación final dentro de la transacción
                if (!$product->active || $product->stock < $quantity) {
                    throw new RuntimeException('Stock no disponible');
                }

                $order->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'unit_price'   => $product->price,
                    'quantity'     => $quantity,
                    'subtotal'     => $product->price * $quantity,
                ]);

                $product->decrement('stock', $quantity);
            }
        });

    } catch (RuntimeException $e) {
        return redirect()->route('cart.index')->with('error', 'No se pudo completar la compra por cambios de disponibilidad. Inténtalo de nuevo.');
    }

    // FASE 3: Limpiar el carrito solo si todo salió bien
    session()->forget('cart');
    return redirect()->route('products.index')->with('success', 'Compra realizada con éxito.');
}
```

---

## Las 3 fases del checkout

### Fase 1 — Validar el carrito (antes de la transacción)

Antes de tocar la base de datos, se verifican dos cosas:

**1. ¿El carrito tiene productos?**
```php
if (empty($cart)) {
    return redirect()->route('cart.index')->with('error', 'Tu carrito está vacío.');
}
```

**2. ¿Todos los productos siguen disponibles?**
```php
if (!$product || !$product->active || $product->stock < $requestedQuantity || $requestedQuantity < 1) {
    return redirect()->route('cart.index')->with('error', '...');
}
```

> 💡 **¿Por qué validar antes de la transacción?**  
> Si el carrito tiene problemas obvios, no tiene sentido iniciar una transacción. Fallar rápido ahorra recursos del servidor.

---

### Fase 2 — La transacción

Esta es la parte más importante. Todo lo que pasa aquí debe completarse **al 100% o no completarse nada**.

```php
DB::transaction(function () use ($cart): void {
    // 1. Calcular total
    // 2. Crear la orden en la tabla orders
    // 3. Por cada producto:
    //    a. Verificar stock una vez más
    //    b. Crear el item en order_items
    //    c. Descontar el stock
});
```

> 💡 **¿Qué es una transacción de base de datos?**  
> Es un conjunto de operaciones que se ejecutan como una sola unidad. Si cualquier operación falla, todas las demás se deshacen automáticamente (ROLLBACK), como si nunca hubieran ocurrido.

**¿Por qué es necesaria?**

Sin transacción podría pasar esto:
```
✅ Orden creada en orders
✅ Item 1 creado en order_items, stock decrementado
✅ Item 2 creado en order_items, stock decrementado
❌ ERROR en Item 3 → el proceso se detiene

Resultado: La orden existe pero le falta un producto.
           El stock de ese producto no se decrementó.
           La base de datos quedó inconsistente.
```

Con transacción:
```
Si cualquier paso falla → ROLLBACK automático
Resultado: Es como si nada hubiera ocurrido. La BD queda intacta.
```

---

### ¿Por qué se vuelve a verificar el stock dentro de la transacción?

```php
// Verificación 1: antes de la transacción (Fase 1)
if ($product->stock < $requestedQuantity) { ... }

// Verificación 2: dentro de la transacción (Fase 2)
if ($product->stock < $quantity) {
    throw new RuntimeException('Stock no disponible');
}
```

Entre la Fase 1 y la Fase 2 puede haber pasado tiempo. En ese tiempo otro usuario pudo haber comprado el mismo producto. La verificación dentro de la transacción garantiza que se trabaja con el dato más reciente.

> 💡 Este problema se llama **TOCTOU** (Time of Check to Time of Use): el estado que verificaste ya no es el estado actual en el momento de usarlo.

---

### Fase 3 — Limpiar el carrito

```php
session()->forget('cart');  // ← SOLO si todo salió bien
return redirect()->route('products.index')->with('success', 'Compra realizada con éxito.');
```

> ⚠️ **¿Por qué limpiar el carrito DESPUÉS y no antes?**  
> Si se limpiara antes y la transacción fallara, el cliente perdería su carrito sin haber completado la compra y tendría que agregar todos los productos de nuevo. Al limpiar solo en caso de éxito, si algo falla el carrito sigue intacto.

---

## Manejo de errores

```php
} catch (RuntimeException $e) {
    return redirect()->route('cart.index')
                     ->with('error', 'No se pudo completar la compra...');
}
```

Cuando se lanza `RuntimeException` dentro de la transacción:
1. Laravel hace **ROLLBACK** automático (deshace todo).
2. El `catch` captura la excepción.
3. El usuario es redirigido al carrito con un mensaje de error claro.

---

## Flujo completo del checkout

```
Cliente da clic en "Pagar"
          ↓
CheckoutController::store()
          ↓
FASE 1: ¿Carrito vacío?        → SÍ → error, volver al carrito
        ¿Productos disponibles? → NO → error, volver al carrito
          ↓ (todo OK)
FASE 2: DB::transaction()
  ├── Calcular total
  ├── Crear orden (orders)
  └── Por cada producto:
        ├── Verificar stock (última vez)  → falla → RuntimeException → ROLLBACK
        ├── Crear item (order_items)
        └── Descontar stock (products)
          ↓ (transacción exitosa)
FASE 3: Limpiar carrito de sesión
        Redirigir a tienda con mensaje de éxito
```

---

## Ruta del checkout

```php
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
```

Solo tiene una ruta POST porque el checkout no tiene página propia: el cliente va directo del carrito a la confirmación.
