# 📦 Introducción al Proyecto — Tienda Básica

> **Nivel:** Principiantes  
> **Framework:** Laravel 12  
> **Lenguaje:** PHP 8.2  

---

## ¿Qué es este proyecto?

Tienda Básica es una aplicación web de comercio electrónico construida con Laravel. Permite a los visitantes ver productos, agregarlos a un carrito de compras y realizar una compra. También tiene un panel de administración donde un administrador puede gestionar los productos de la tienda.

---

## ¿Qué puede hacer la tienda?

### Lo que ve el cliente (parte pública)
- Ver el catálogo de productos disponibles
- Ver el detalle de cada producto (nombre, precio, descripción, imagen)
- Agregar productos al carrito de compras
- Modificar cantidades o eliminar productos del carrito
- Realizar una compra (checkout)

### Lo que puede hacer el administrador (panel privado)
- Iniciar sesión con usuario y contraseña
- Ver la lista de todos los productos
- Crear productos nuevos
- Editar productos existentes
- Eliminar productos
- Activar o desactivar productos

---

## Tecnologías usadas

| Tecnología | ¿Para qué se usa? |
|---|---|
| Laravel 12 | Framework principal de PHP para construir la aplicación |
| PHP 8.2 | Lenguaje de programación del servidor |
| MySQL | Base de datos donde se guardan productos, órdenes y usuarios |
| Blade | Motor de plantillas de Laravel para crear las vistas HTML |
| Tailwind CSS | Framework de estilos para diseñar las páginas |
| Bootstrap | Librería adicional de estilos |
| Eloquent ORM | Sistema de Laravel para trabajar con la base de datos |
| Sesiones de PHP | Para guardar el carrito de compras temporalmente |

---

## Estructura de carpetas importantes

```
tienda-basica/
│
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── AdminProductController.php  ← gestiona productos en el admin
│   │   │   │   └── AuthController.php          ← login y logout del admin
│   │   │   ├── CartController.php              ← gestiona el carrito
│   │   │   ├── CheckoutController.php          ← procesa la compra
│   │   │   └── ProductController.php           ← muestra productos al cliente
│   │   └── Middleware/
│   │       └── IsAdmin.php                     ← protege el panel admin
│   └── Models/
│       ├── Order.php                           ← representa una orden de compra
│       ├── OrderItem.php                       ← representa un producto dentro de una orden
│       ├── Product.php                         ← representa un producto
│       └── User.php                            ← representa un usuario
│
├── database/
│   ├── migrations/                             ← instrucciones para crear la BD
│   └── seeders/
│       └── AdminUserSeeder.php                 ← crea el usuario administrador
│
├── resources/
│   └── views/
│       ├── admin/
│       │   ├── login.blade.php                 ← formulario de login del admin
│       │   └── products/
│       │       ├── index.blade.php             ← lista de productos en el admin
│       │       ├── create.blade.php            ← formulario para crear producto
│       │       ├── edit.blade.php              ← formulario para editar producto
│       │       └── _form.blade.php             ← formulario reutilizable
│       ├── cart/
│       │   └── index.blade.php                ← página del carrito
│       ├── products/
│       │   ├── index.blade.php                ← catálogo de productos
│       │   └── show.blade.php                 ← detalle de un producto
│       └── layouts/
│           └── app.blade.php                  ← plantilla base de todas las páginas
│
├── routes/
│   └── web.php                                ← todas las rutas de la aplicación
│
└── docs/                                      ← documentación del proyecto
    ├── 01-introduccion.md                     ← este archivo
    ├── 02-base-de-datos.md
    ├── 03-modelos.md
    ├── 04-carrito.md
    ├── 05-checkout.md
    ├── 06-productos.md
    ├── 07-admin-productos.md
    └── 08-autenticacion-admin.md
```

---

## ¿Cómo instalar el proyecto?

### Requisitos previos
- PHP 8.2 o superior
- Composer
- MySQL
- Node.js y npm

### Pasos de instalación

**1. Clonar el repositorio:**
```bash
git clone https://github.com/chalarca14/Tienda-basica.git
cd Tienda-basica
```

**2. Instalar dependencias de PHP:**
```bash
composer install
```

**3. Instalar dependencias de JavaScript:**
```bash
npm install
npm run dev
```

**4. Configurar el archivo de entorno:**
```bash
cp .env.example .env
php artisan key:generate
```

**5. Configurar la base de datos en `.env`:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tienda_basica
DB_USERNAME=root
DB_PASSWORD=
```

**6. Crear las tablas en la base de datos:**
```bash
php artisan migrate
```

**7. Crear el enlace de almacenamiento para imágenes:**
```bash
php artisan storage:link
```

**8. Crear el usuario administrador:**
```bash
php artisan db:seed --class=AdminUserSeeder
```

**9. Iniciar el servidor:**
```bash
php artisan serve
```

**10. Abrir en el navegador:**
```
http://127.0.0.1:8000
```

---

## Credenciales del administrador

| Campo | Valor |
|---|---|
| Email | admin@tienda.com |
| Contraseña | admin123 |
| URL del panel | http://127.0.0.1:8000/admin/login |

> ⚠️ Cambiar estas credenciales antes de publicar el sitio.

---

## Documentación por secciones

| Archivo | Contenido |
|---|---|
| [02-base-de-datos.md](02-base-de-datos.md) | Tablas, columnas y relaciones |
| [03-modelos.md](03-modelos.md) | Modelos Eloquent y sus relaciones |
| [04-carrito.md](04-carrito.md) | Cómo funciona el carrito de compras |
| [05-checkout.md](05-checkout.md) | Cómo se procesa una compra |
| [06-productos.md](06-productos.md) | Cómo se muestran los productos al cliente |
| [07-admin-productos.md](07-admin-productos.md) | Panel de administración de productos |
| [08-autenticacion-admin.md](08-autenticacion-admin.md) | Sistema de login del administrador |
| [09-vistas.md](09-vistas.md) | vistas |
