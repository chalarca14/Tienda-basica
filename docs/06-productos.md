# 🛍️ Catálogo de Productos (Vista del Cliente)

> Aquí se explica cómo se muestran los productos al cliente en la parte pública de la tienda.

---

## ¿Qué ve el cliente?

La parte pública de la tienda tiene dos páginas de productos:

1. **Catálogo** (`/products`) — Lista de todos los productos activos con paginación.
2. **Detalle** (`/products/{id}`) — Página individual de un producto específico.

---

## Archivo: `ProductController.php`

Este controlador maneja las dos páginas públicas de productos. Es sencillo porque solo muestra información, no modifica nada.

```php
class ProductController extends Controller
{
    // Mostrar todos los productos (tienda)
    public function index()
    {
        $products = Product::where('active', true)
            ->paginate(12);
        return view('products.index', compact('products'));
    }

    // Mostrar detalle del producto
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }
}
```

---

## Método `index()` — Catálogo de productos

```php
public function index()
{
    $products = Product::where('active', true)->paginate(12);
    return view('products.index', compact('products'));
}
```

### ¿Qué hace paso a paso?

**`Product::where('active', true)`**
Filtra solo los productos activos. Los productos con `active = false` están ocultos para el cliente aunque existan en la base de datos.

> 💡 ¿Por qué no mostrar todos los productos?  
> Un administrador puede desactivar un producto porque está agotado, en revisión o descontinuado. El cliente solo debe ver lo que puede comprar.

**`.paginate(12)`**
Divide los resultados en páginas de 12 productos cada una. En lugar de cargar todos los productos de la base de datos de una vez, solo carga los 12 de la página actual.

> 💡 **¿Por qué paginar?**  
> Si la tienda tiene 500 productos y se cargaran todos a la vez:
> - La consulta a la BD sería muy pesada.
> - La página HTML sería enorme y lenta de cargar.
> - El navegador tendría que renderizar 500 elementos.
> 
> Con paginación se cargan solo 12 productos por página, lo cual es mucho más rápido.

**`return view('products.index', compact('products'))`**
Envía los productos a la vista Blade para que los muestre. `compact('products')` convierte la variable `$products` en un array que Blade puede usar.

---

## Método `show()` — Detalle de un producto

```php
public function show(Product $product)
{
    return view('products.show', compact('product'));
}
```

### ¿Qué hace?

Recibe un producto y lo muestra en su página de detalle.

**`Product $product`** — Esto es **Route Model Binding**. En lugar de recibir un `$id` y luego buscar el producto manualmente, Laravel hace la búsqueda automáticamente:

```php
// Sin Route Model Binding (manual y verboso):
public function show($id)
{
    $product = Product::find($id);
    if (!$product) {
        abort(404);
    }
    return view('products.show', compact('product'));
}

// Con Route Model Binding (automático y limpio):
public function show(Product $product)
{
    // Laravel ya buscó el producto y lanzó 404 si no existe
    return view('products.show', compact('product'));
}
```

> 💡 **Ventajas del Route Model Binding:**
> - Menos código.
> - Si el ID no existe, Laravel lanza automáticamente error 404.
> - Más legible y expresivo.

---

## Rutas del catálogo

```php
Route::get('/',                   [ProductController::class, 'index'])->name('home');
Route::get('/products',           [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
```

| Ruta | Método | ¿Qué muestra? |
|---|---|---|
| `/` | GET | Página principal (mismo catálogo) |
| `/products` | GET | Catálogo de productos con paginación |
| `/products/{id}` | GET | Detalle de un producto específico |

> 💡 Tanto `/` como `/products` apuntan al mismo método `index()`. La página principal y el catálogo muestran lo mismo.

---

## ¿Qué pasa si alguien intenta ver un producto inactivo?

Con el código actual, si alguien escribe directamente la URL de un producto inactivo (como `/products/5`), Laravel lo mostraría igual porque `show()` no verifica si está activo.

Una mejora posible sería agregar esta verificación:

```php
public function show(Product $product)
{
    // Si el producto está inactivo, mostrar error 404
    if (!$product->active) {
        abort(404);
    }
    return view('products.show', compact('product'));
}
```

---

## Resumen del flujo

```
Cliente entra a /products
        ↓
ProductController::index()
  Consulta: productos donde active = true, de 12 en 12
        ↓
Vista products/index.blade.php
  Muestra los 12 productos de la página actual
  Muestra los botones de paginación
        ↓
Cliente da clic en un producto
        ↓
ProductController::show($product)
  Laravel busca el producto por ID automáticamente
        ↓
Vista products/show.blade.php
  Muestra nombre, descripción, precio, imagen
  Botón "Agregar al carrito" → CartController::add()
```
