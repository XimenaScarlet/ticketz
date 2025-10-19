# TicketZ (PHP + SQLite)
Sistema de tickets ligero con login, registro, perfil, creación y seguimiento de tickets, listado de agentes y configuración básica.

## Requisitos
- PHP 8.1+ con extensiones `pdo_sqlite` y `sqlite3` habilitadas (vienen en PHP por defecto).
- No requiere Composer ni Node.

## Iniciar
1. Descomprime el ZIP.
2. Entra a la carpeta `ticketz-php-sqlite` en tu terminal.
3. Ejecuta:
   ```bash
   php -S localhost:8080 -t public
   ```
4. Abre http://localhost:8080 en tu navegador.

> La base de datos SQLite se crea automáticamente en `data/app.db` al primer arranque.

## Cuentas demo
- Admin: `admin@demo.local` / `Admin123!`
- Agente: `agent@demo.local` / `Agent123!`
- Usuario: `user@demo.local` / `User123!`

## Características
- Autenticación (registro, inicio/cierre de sesión).
- Perfil con cambio de nombre, tema (claro/oscuro) y contraseña.
- Dashboard con KPI básicos y actividad reciente.
- Creación de tickets con categoría, prioridad y descripción.
- Lista de tickets propios con filtros (abierto, pendiente, cerrado).
- Vista de ticket con comentarios, cambio de estado y asignación a agente.
- Cola global para agentes/admin.
- Lista de agentes con última actividad (estado en línea aproximado).

## Notas
- Estilos con [Pico.css](https://picocss.com) + un CSS simple.
- Seguridad básica: sesiones, contraseñas con `password_hash`, CSRF tokens y consultas preparadas.
- Para resetear la DB, borra el archivo `data/app.db` con el servidor detenido.

¡Disfruta! 🎫
