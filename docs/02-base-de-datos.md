# 🗄️ Base de Datos

> Aquí se explica cómo está organizada la información en la base de datos de la tienda.

---

## ¿Qué es una base de datos?

Una base de datos es el lugar donde se guarda toda la información de la aplicación: los productos, las órdenes de compra, los usuarios, etc. Está organizada en **tablas**, que son como hojas de Excel donde cada fila es un registro y cada columna es un dato.

---

## Tablas del proyecto

El proyecto tiene **6 tablas** principales:

| Tabla | ¿Qué guarda? |
|---|---|
| `users` | Los usuarios del sistema (clientes y administradores) |
| `products` | Los productos de la tienda |
| `orders` | Las órdenes de compra realizadas |
| `order_items` | Los productos dentro de cada orden |
| `sessions` | Las sesiones activas de los usuarios |
| `cache` | Caché interno de Laravel |

---

## Tabla `users`

Guarda la información de todos los usuarios. Los administradores se distinguen por el campo `is_admin`.

| Columna | Tipo | ¿Puede ser vacío? | Descripción |
|---|---|---|---|
| `id` | bigint | NO | Identificador único, se incrementa automáticamente |
| `name` | varchar(255) | NO | Nombre del usuario |
| `email` | varchar(255) | NO | Correo electrónico, debe ser único |
| `email_verified_at` | timestamp | SÍ | Fecha en que verificó su email |
| `password` | varchar(255) | NO | Contraseña encriptada con bcrypt |
| `is_admin` | boolean | NO | `true` = administrador, `false` = usuario normal |
| `remember_token` | varchar(100) | SÍ | Token para la función "recuérdame" |
| `created_at` | timestamp | SÍ | Fecha de creación del registro |
| `updated_at` | timestamp | SÍ | Fecha de última modificación |

---

## Tabla `products`

Guarda todos los productos de la tienda, tanto los activos como los inactivos.

| Columna | Tipo | ¿Puede ser vacío? | Descripción |
|---|---|---|---|
| `id` | bigint | NO | Identificador único |
| `name` | varchar(255) | NO | Nombre del producto |
| `description` | text | SÍ | Descripción del producto |
| `price` | decimal(10,2) | NO | Precio con dos decimales exactos |
| `stock` | integer | NO | Unidades disponibles en inventario |
| `image` | varchar(255) | SÍ | Ruta de la imagen del producto |
| `active` | boolean | NO | `true` = visible en la tienda, `false` = oculto |
| `created_at` | timestamp | SÍ | Fecha de creación |
| `updated_at` | timestamp | SÍ | Fecha de última modificación |

> 💡 **¿Por qué `price` es `decimal` y no `float`?**  
> El tipo `float` tiene errores de redondeo. Por ejemplo, `0.1 + 0.2` puede dar `0.30000000000000004`. El tipo `decimal(10,2)` guarda exactamente dos decimales, lo cual es obligatorio para valores de dinero.

---

## Tabla `orders`

Guarda el encabezado de cada compra realizada. Una orden puede tener varios productos.

| Columna | Tipo | ¿Puede ser vacío? | Descripción |
|---|---|---|---|
| `id` | bigint | NO | Identificador único |
| `user_id` | bigint | SÍ | ID del usuario que compró (puede ser NULL) |
| `total` | decimal(10,2) | NO | Total de la compra |
| `status` | varchar | NO | Estado de la orden (ej: `paid`) |
| `placed_at` | timestamp | SÍ | Fecha en que se realizó la compra |
| `created_at` | timestamp | SÍ | Fecha de creación del registro |
| `updated_at` | timestamp | SÍ | Fecha de última modificación |

> 💡 **¿Por qué `user_id` puede ser NULL?**  
> Si un usuario es eliminado del sistema, sus órdenes no se borran. El campo queda en NULL pero la orden sigue existiendo. Esto protege el historial de ventas.

---

## Tabla `order_items`

Guarda cada producto dentro de una orden. Si una orden tiene 3 productos, habrá 3 registros en esta tabla.

| Columna | Tipo | ¿Puede ser vacío? | Descripción |
|---|---|---|---|
| `id` | bigint | NO | Identificador único |
| `order_id` | bigint | NO | ID de la orden a la que pertenece |
| `product_id` | bigint | SÍ | ID del producto (puede ser NULL si se elimina) |
| `product_name` | varchar(255) | NO | Nombre del producto al momento de la compra |
| `unit_price` | decimal(10,2) | NO | Precio del producto al momento de la compra |
| `quantity` | integer | NO | Cantidad comprada |
| `subtotal` | decimal(10,2) | NO | `unit_price × quantity` |
| `created_at` | timestamp | SÍ | Fecha de creación |
| `updated_at` | timestamp | SÍ | Fecha de última modificación |

> 💡 **¿Por qué se guarda `product_name` y `unit_price` si ya está el `product_id`?**  
> Porque los precios cambian con el tiempo y los productos pueden eliminarse. Si solo se guardara el ID, al consultar una orden antigua se vería el precio actual (incorrecto) o daría error si el producto fue eliminado. Guardar el nombre y precio en el momento de la compra garantiza que el historial sea siempre exacto.

---

## Relaciones entre tablas

Las tablas se conectan entre sí mediante **claves foráneas** (foreign keys). Así funciona:

```
users
  └── orders (un usuario puede tener muchas órdenes)
        └── order_items (una orden puede tener muchos items)
              └── products (cada item apunta a un producto)
```

### En detalle:

**Un usuario tiene muchas órdenes:**
```
users.id  →  orders.user_id
```

**Una orden tiene muchos items:**
```
orders.id  →  order_items.order_id
```

**Un item pertenece a un producto:**
```
products.id  →  order_items.product_id
```

---

## ¿Qué pasa cuando se elimina un registro?

| Si se elimina... | ¿Qué pasa con los registros relacionados? |
|---|---|
| Un **usuario** | Sus órdenes permanecen pero `user_id` queda en `NULL` |
| Una **orden** | Sus `order_items` se eliminan automáticamente (cascada) |
| Un **producto** | Los `order_items` que lo referencian quedan con `product_id = NULL`, pero conservan `product_name` y `unit_price` |

---

## Migraciones del proyecto

Las migraciones son los archivos que crean estas tablas. Se ejecutan con:

```bash
php artisan migrate
```

Para revertir todos los cambios:
```bash
php artisan migrate:rollback
```

Para ver el estado de las migraciones:
```bash
php artisan migrate:status
```
