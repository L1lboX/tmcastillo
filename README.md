# Transportes Castillo

Sistema Laravel para gestionar clientes y envios.

## Requisitos

- PHP 8.3+
- Composer
- Node.js
- MySQL

## Acceso inicial

Despues de ejecutar migraciones y seeders:

```bash
php artisan migrate --seed
```

Usuario inicial:

- Usuario: `admin`
- Contrasena: `admin12345`

Cambia esos valores en `.env` antes de sembrar produccion:

```env
ADMIN_NAME="Super Admin"
ADMIN_USERNAME=admin
ADMIN_EMAIL=admin@transportescastillo.local
ADMIN_PASSWORD=una-clave-segura
```

## Instalacion

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

La app queda en `http://127.0.0.1:8000`.

## Base de datos

Configura estas variables en `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=castillo_envios
DB_USERNAME=root
DB_PASSWORD=
```

## API

- `GET /api/envios`
- `POST /api/envios`
- `GET /api/envios/{id}`
- `PUT /api/envios/{id}`
- `DELETE /api/envios/{id}`
- `GET /api/clientes?search=texto`
- `POST /api/clientes`
- `GET /api/stats`

## Produccion en hosting compartido

No uses `composer dev` en produccion. Compila Vite localmente y sube `public/build`.

```bash
composer install --no-dev --optimize-autoloader
npm run build
php artisan key:generate
php artisan migrate --force --seed
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

El dominio debe apuntar a la carpeta `public` del proyecto Laravel.
