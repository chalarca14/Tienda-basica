# 🧩 Modelos Eloquent

> Aquí se explica qué son los modelos y cómo funcionan los del proyecto.

---

## ¿Qué es un modelo?

En Laravel, un modelo es un archivo PHP que representa una tabla de la base de datos. Es el intermediario entre el código y la base de datos. En lugar de escribir SQL a mano, usamos los modelos para leer, crear, actualizar y eliminar registros de forma sencilla.

> 💡 **Analogía:** Si la base de datos es un almacén, el modelo es el empleado que sabe exactamente dónde está cada cosa y cómo pedirla.

---

## ¿Qué es Eloquent?

Eloquent es el sistema de modelos de Laravel. Permite hacer cosas como:

```php
// Sin Eloquent (SQL a mano):
SELECT * FROM products WHERE active = 1 LIMIT 12;

// Con Eloquent (mucho más sencillo):
Product::where('active', true)->paginate(12);
```

---

## Modelo `Product.php`

Representa la tabla `products`. Es el modelo más importante de la tienda.

```php
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image',
        'active'
    ];

    protected $casts = [
        'price'  => 'decimal:2',
        'active' => 'boolean'
    ];
}
```

### ¿Qué hace cada parte?

**`$fillable`**
Lista de campos que se pueden guardar masivamente con `create()` o `update()`. Es una medida de seguridad: si alguien envía campos extra en un formulario (como `is_admin`), Laravel los ignora porque no están en la lista.

**`$casts`**
Le dice a Laravel cómo convertir los valores al leerlos de la base de datos:

| Campo | Cast | ¿Qué hace? |
|---|---|---|
| `price` | `decimal:2` | Garantiza que el precio siempre tenga exactamente 2 decimales |
| `active` | `boolean` | Convierte `1`/`0` de MySQL a `true`/`false` de PHP |

**`HasFactory`**
Permite crear productos de prueba de forma automática en los tests.

---

## Modelo `Order.php`

Representa la tabla `orders`. Guarda el encabezado de cada compra.

```php
class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total',
        'status',
        'placed_at',
    ];

    protected $casts = [
        'total'     => 'decimal:2',
        'placed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
```

### ¿Qué hace cada parte?

**`$casts`**

| Campo | Cast | ¿Qué hace? |
|---|---|---|
| `total` | `decimal:2` | El total siempre tiene exactamente 2 decimales |
| `placed_at` | `datetime` | Convierte el texto de la BD a un objeto de fecha de PHP |

**Relación `items()`**
Define que una orden tiene muchos `OrderItem`. Es una relación de uno a muchos.

```php
// Ejemplo de uso:
$order = Order::find(1);
$order->items;  // devuelve todos los productos de esa orden
```

> 💡 **¿Qué es `HasMany`?**  
> Es el tipo de relación "uno tiene muchos". Una orden puede tener 1, 5 o 20 items. La clave foránea `order_id` está en la tabla `order_items`.

---

## Modelo `OrderItem.php`

Representa la tabla `order_items`. Guarda cada producto dentro de una orden.

```php
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

### ¿Qué hace cada parte?

**`$casts`**

| Campo | Cast | ¿Qué hace? |
|---|---|---|
| `unit_price` | `decimal:2` | El precio unitario siempre tiene 2 decimales |
| `subtotal` | `decimal:2` | El subtotal siempre tiene 2 decimales |

**Relación `order()`**
Define que este item pertenece a una orden. Permite navegar del item hacia su orden.

```php
$item = OrderItem::find(1);
$item->order;  // devuelve la orden a la que pertenece este item
```

**Relación `product()`**
Define que este item pertenece a un producto. Permite acceder a la información actual del producto.

```php
$item->product;        // devuelve el producto actual
$item->product->image; // imagen actual del producto
$item->unit_price;     // precio histórico que se pagó (snapshot)
```

> 💡 **¿Por qué tiene dos relaciones `belongsTo`?**  
> Porque `OrderItem` es el punto de unión entre una orden y un producto. Necesita poder navegar hacia ambos lados: hacia la orden que lo contiene y hacia el producto al que hace referencia.

---

## Modelo `User.php`

Representa la tabla `users`. Maneja tanto clientes como administradores.

```php
class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_admin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_admin'          => 'boolean',
    ];

    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }
}
```

### ¿Qué hace cada parte?

**`$hidden`**
Lista de campos que nunca se exponen cuando el modelo se convierte a JSON o array. La contraseña y el token de sesión nunca deben ser visibles.

**`$casts`**

| Campo | Cast | ¿Qué hace? |
|---|---|---|
| `email_verified_at` | `datetime` | Convierte el texto de la BD a objeto de fecha |
| `password` | `hashed` | Laravel encripta automáticamente la contraseña al guardarla |
| `is_admin` | `boolean` | Convierte `1`/`0` a `true`/`false` |

**Método `isAdmin()`**
Método helper para verificar si el usuario es administrador. Se usa en el middleware y en el controlador de autenticación.

```php
// En lugar de escribir esto en cada lugar:
if ($user->is_admin === true) { ... }

// Se escribe esto:
if ($user->isAdmin()) { ... }
```

---

## Resumen de relaciones

```
User
  └── (no tiene relación directa con Order en el código,
       pero orders.user_id apunta a users.id)

Order
  └── items()  →  tiene muchos OrderItem  (HasMany)

OrderItem
  ├── order()    →  pertenece a Order    (BelongsTo)
  └── product()  →  pertenece a Product  (BelongsTo)

Product
  └── (no define relaciones en el modelo,
       pero order_items.product_id apunta a products.id)
```

---

## Comandos útiles con Eloquent

```php
// Obtener todos los productos activos
Product::where('active', true)->get();

// Obtener con paginación
Product::where('active', true)->paginate(12);

// Crear un producto
Product::create(['name' => 'Camisa', 'price' => 50000, ...]);

// Buscar por ID (lanza error si no existe)
Product::findOrFail(1);

// Actualizar
$product->update(['price' => 55000]);

// Eliminar
$product->delete();

// Obtener orden con sus items
$order = Order::with('items')->find(1);
$order->items; // colección de OrderItem
```
