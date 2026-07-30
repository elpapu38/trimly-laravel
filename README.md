<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="260" alt="Laravel">
</p>

<h1 align="center">💈 Trimly</h1>
<p align="center"><b>Plataforma de reserva de turnos online para barberías, peluquerías, salones de belleza, spas y estudios de tatuajes.</b></p>

---

## 🔑 Usuarios de prueba

Para probar la aplicación sin necesidad de crear cuentas nuevas, ya vienen cargados estos usuarios de ejemplo en la base de datos:

| Email | Contraseña | Rol | Qué puede hacer |
|---|---|---|---|
| `admin@trimly.com` | `admin123` | **superadmin** | Administra toda la plataforma: aprueba/suspende locales, modera reseñas, ve estadísticas globales |
| `carlos@barberia.com` | `carlos123` | **shop_owner** (Barbería El Navajo) | Dueño de un local: gestiona empleados, servicios, horarios, turnos y estadísticas de su negocio |
| `lucas@gmail.com` | `wazawaza` | **employee** (Barbería El Navajo) | Empleado del local: ve y gestiona su propia agenda de turnos |
| `cliente.test@trimly.com` | `wazawaza` | **client** | Cliente final: reserva turnos, deja reseñas, marca favoritos |

> ⚠️ Estas credenciales son solo para el entorno de desarrollo/demo. **Nunca deben usarse en un servidor de producción real.**

---

## 📖 ¿Qué es Trimly? (explicado sin tecnicismos)

Trimly es una web parecida a lo que serían "Booksy" o "Fresha", pero hecha a medida: un lugar donde una persona puede buscar una barbería o salón de belleza cerca suyo, ver sus servicios y precios, elegir un empleado, elegir un horario libre y reservar el turno en pocos clics — sin necesidad de llamar por teléfono.

Del otro lado, el dueño del local tiene un panel donde carga sus servicios, sus empleados, sus horarios de atención, y desde ahí ve y administra todos los turnos que le van entrando, además de estadísticas de facturación y reseñas de sus clientes.

Y por encima de todo eso hay un panel de **superadministrador**, que es quien controla la plataforma en general: aprueba que un local nuevo se sume a Trimly, puede suspender locales o usuarios que rompan las reglas, y modera reseñas reportadas como abusivas.

En resumen, hay **cuatro tipos de usuario**, cada uno con su propia pantalla y sus propios permisos:

1. **Cliente** — busca locales, reserva turnos, deja reseñas, guarda favoritos.
2. **Empleado** (barbero, estilista, etc.) — ve su agenda de turnos asignados, gestiona sus servicios y su perfil.
3. **Dueño de local** (`shop_owner`) — administra su negocio: empleados, servicios, horarios, turnos, reseñas y estadísticas.
4. **Superadmin** — administra toda la plataforma: aprueba locales, banea/suspende cuentas, modera contenido, ve estadísticas globales.

También se puede reservar un turno **sin crear una cuenta** (como invitado), dejando nombre, email y teléfono.

---

## 🧩 Funcionalidades principales

- **Búsqueda pública de locales** por nombre, ciudad, tipo de rubro (barbería, salón, spa, tatuajes, etc.) y público objetivo (hombres, mujeres, unisex).
- **Ficha de cada local**: fotos, descripción, servicios con precio y duración, horarios de atención, reseñas y calificación promedio.
- **Reserva de turno en 4 pasos**: elegir servicio → elegir empleado → elegir fecha/horario disponible → confirmar datos. El sistema calcula automáticamente qué horarios están realmente libres, cruzando el horario del local, el horario particular del empleado, los turnos ya ocupados y los bloqueos manuales (ej. vacaciones, almuerzo).
- **Confirmación y cancelación por email**, con un enlace único (token) que no requiere iniciar sesión — útil para quienes reservan como invitados.
- **Panel del dueño de local**: agenda tipo calendario, listado de turnos con cambio de estado (pendiente → confirmado → completado / cancelado / no-show), alta y edición de empleados y servicios, carga de fotos del local, configuración de horarios por día, respuesta a reseñas, y estadísticas de facturación.
- **Panel del empleado**: su propia agenda, historial de turnos atendidos, carga manual de turnos (por ejemplo si un cliente llama por teléfono), gestión de qué servicios realiza, foto de perfil y galería de trabajos.
- **Panel de superadministrador**: aprobación/rechazo de locales nuevos, suspensión o baneo de locales y usuarios (con motivo y fecha de vencimiento), moderación de reseñas reportadas, estadísticas globales de la plataforma (ingresos, turnos, locales activos) y un registro de auditoría de las acciones que toma el propio staff.
- **Sistema de reseñas** con calificación de 1 a 5 estrellas, posibilidad de reportar una reseña ofensiva y de que el local le responda públicamente.
- **Favoritos**: un cliente puede "guardar" un local para encontrarlo rápido después.
- **Registro de nuevos locales**: cualquier usuario logueado puede pedir dar de alta su propio negocio, que queda "pendiente" hasta que el superadmin lo aprueba.
- **Notificaciones por email** para verificación de cuenta, recuperación de contraseña, confirmación de reserva y aprobación de un local nuevo.

---

## 🛠️ Cómo está construido (explicación técnica)

| Aspecto | Detalle |
|---|---|
| **Framework** | [Laravel 12](https://laravel.com) (PHP 8.2+) |
| **Frontend** | Blade (plantillas del propio Laravel, renderizadas en el servidor) + [Tailwind CSS](https://tailwindcss.com) para los estilos. **No** usa React, Vue ni ningún framework de JavaScript — la interactividad puntual se resuelve con JS simple y llamadas a rutas tipo API internas (`/api/slots`, `/api/empleados-para-servicio`) para cargar horarios disponibles sin recargar la página. |
| **Base de datos** | MySQL / MariaDB. El esquema completo (21 tablas) y datos de ejemplo se importan desde un dump SQL (`database/trimly-laravel.sql`) en vez de usar migraciones de Laravel para las tablas propias del negocio — ver sección de Debilidades. |
| **Autenticación** | Sistema de login/registro **propio**, escrito a mano (no usa Laravel Breeze/Jetstream/Fortify), con verificación de cuenta por email, recuperación de contraseña y control de estado de cuenta (activa / suspendida / baneada). |
| **Autorización** | Basada en roles simples (`client`, `employee`, `shop_owner`, `superadmin`) mediante un campo `role` en la tabla `users`, verificado por un middleware propio (`EnsureRole`). No usa Policies/Gates nativas de Laravel ni paquetes como Spatie Permissions. |
| **Envío de emails** | Mailables de Laravel (`app/Mail/`) para verificación de cuenta, reseteo de contraseña, confirmación de turno y aprobación de local. |
| **Subida de imágenes** | Servicio propio (`app/Services/ImageUploader.php`) que valida tipo y tamaño de archivo y las guarda en el disco público de Laravel (`storage/app/public`). |
| **Colas / trabajos en segundo plano** | Configuradas (driver `database`), aunque el proyecto no trae jobs propios más allá de lo que trae Laravel por defecto. |
| **Pagos online** | El modelo de datos contempla pagos (`payment_option`: efectivo/seña/online, `payment_status`, `payment_ref`), pero **no hay integración real con una pasarela de pago** (Mercado Pago, Stripe, etc.) — los campos existen mayormente para reflejar datos de ejemplo cargados a mano. |
| **Tests automáticos** | Trae la configuración estándar de PHPUnit, pero solo con los tests de ejemplo que genera Laravel por defecto — no hay tests propios del negocio (reservas, roles, etc.). |

### Estructura de carpetas (resumen)

```
app/
 ├─ Http/Controllers/   → 18 controladores (uno por área: reservas, panel dueño, panel empleado, admin, etc.)
 ├─ Http/Middleware/    → control de rol, estado de cuenta y flujo de reserva
 ├─ Models/             → 21 modelos Eloquent (Shop, Appointment, Employee, Review, etc.)
 ├─ Mail/               → plantillas de los emails que envía el sistema
 └─ Services/           → lógica reutilizable (ej. subida de imágenes)
resources/views/        → 62 plantillas Blade, organizadas por sección (booking, shop_dash, admin, etc.)
database/
 ├─ migrations/         → solo las tablas base de Laravel (users, cache, jobs)
 └─ trimly-laravel.sql  → dump completo del esquema real + datos de ejemplo
routes/web.php          → todas las rutas de la aplicación, agrupadas por rol
```

---

## 🚀 Cómo instalarlo y correrlo localmente

**Requisitos:** PHP 8.2+, Composer, Node.js + npm, y un servidor MySQL/MariaDB.

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Configurar el entorno
cp .env.example .env      # si no existe .env.example, crear el archivo a mano (ver más abajo)
php artisan key:generate

# 3. Configurar la base de datos en el archivo .env
#    DB_CONNECTION=mysql
#    DB_DATABASE=trimly-laravel
#    DB_USERNAME=...
#    DB_PASSWORD=...

# 4. Crear la base de datos vacía (con el nombre que pusiste en .env) e importar el dump
mysql -u root -p trimly-laravel < database/trimly-laravel.sql

# 5. Enlazar el almacenamiento público (para que se vean las imágenes subidas)
php artisan storage:link

# 6. Compilar los estilos (Tailwind) y levantar el servidor
npm run build
php artisan serve
```

La aplicación quedará disponible en `http://localhost:8000`.

> 📌 **Importante:** este proyecto **no trae un archivo `.env.example`** en el repositorio, así que hay que crear el `.env` a mano con al menos las variables de conexión a la base de datos y, opcionalmente, las de envío de correo (`MAIL_MAILER`, etc. — por defecto usa el driver `log`, que simplemente escribe los emails en `storage/logs/laravel.log` en vez de enviarlos de verdad).

---

## ✅ Fortalezas del proyecto

- **Separación de roles muy clara**: cada tipo de usuario tiene sus propias rutas, controladores y vistas, lo que hace fácil entender "qué puede hacer cada quién" con solo mirar `routes/web.php`.
- **Lógica de reservas robusta**: el cálculo de horarios disponibles (`Appointment::getAvailableSlots`) contempla varios casos reales a la vez — horario particular del empleado vs. horario general del local, turnos ya tomados, bloqueos manuales y hasta el margen mínimo para reservar "para ya" el mismo día.
- **Buen manejo de estados de cuenta**: el sistema de suspensión/baneo con fecha de vencimiento automática (`CheckAccountStatus`) es un detalle que muchos proyectos chicos no contemplan.
- **Flujo de invitado bien pensado**: se puede reservar un turno sin registrarse, y aun así cancelarlo o verlo después gracias a los tokens únicos por turno — buena decisión de experiencia de usuario.
- **Panel de administración completo**: no es solo un CRUD, incluye moderación real (aprobar/rechazar locales, resolver reportes de reseñas) y un registro de auditoría de las acciones del staff.
- **Helpers de vista prolijos** (`app/helpers.php`): funciones como `money()`, `fecha()`, `duracionTexto()` mantienen las plantillas Blade limpias y consistentes en formato.
- **Stack simple y liviano**: al no depender de un framework de JavaScript, el proyecto es más fácil de levantar y entender para alguien que recién empieza con Laravel.

## ⚠️ Debilidades y puntos a mejorar

- **El esquema de base de datos no vive en migraciones de Laravel.** Las 21 tablas del negocio (turnos, locales, empleados, etc.) están definidas en un dump SQL exportado desde phpMyAdmin, en vez de en migraciones versionadas. Esto significa que **no se puede recrear la base con `php artisan migrate`**, se pierde el historial de cambios de esquema, y es más difícil de mantener en equipo o desplegar en distintos entornos (desarrollo, testing, producción) de forma prolija.
- **No hay archivo `.env.example`**, lo que obliga a cualquier persona nueva en el proyecto a adivinar qué variables de entorno necesita configurar.
- **Prácticamente no hay tests automáticos propios.** Solo están los tests de ejemplo que trae Laravel por defecto (`ExampleTest.php`), sin cobertura real de la lógica de negocio (reservas, permisos, pagos, etc.). Esto hace más riesgoso modificar el código a futuro sin romper algo sin darse cuenta.
- **Autenticación y autorización hechas a mano.** Al no usar Laravel Breeze/Fortify ni un paquete de permisos como Spatie, se pierde parte del trabajo de seguridad ya probado por la comunidad (por ejemplo, límites de intentos de login o protecciones extra), y cualquier cambio en las reglas de rol hay que mantenerlo manualmente en el middleware `EnsureRole`.
- **No hay integración real de pagos online.** Los campos de pago (`payment_option`, `payment_ref`, etc.) existen en la base, pero no hay conexión con una pasarela real (Mercado Pago, Stripe, etc.); los "pagos online" del set de datos de ejemplo son simplemente valores cargados a mano.
- **El envío de emails depende de la configuración del servidor.** Por defecto usa el driver `log` (no manda correos reales), así que funciones como verificación de cuenta o recuperación de contraseña no van a llegar a una bandeja de entrada real hasta configurar un proveedor de correo (SMTP, Mailgun, etc.) en el `.env`.
- **Datos de ejemplo mezclados con el esquema.** El dump SQL trae turnos, reseñas y usuarios de prueba ya cargados junto con la estructura de las tablas, lo cual es cómodo para probar la demo pero no es lo ideal para levantar un ambiente de producción limpio (haría falta separar "estructura" de "datos semilla").
- **Sin API pública ni documentación de API.** Las dos rutas tipo API que existen (`/api/slots`, `/api/empleados-para-servicio`) son de uso interno para el frontend y no están pensadas ni protegidas como una API pública consumible por terceros.

---

## 🗺️ Ideas para futuras mejoras

- Migrar el esquema de base de datos a migraciones reales de Laravel (con seeders separados para los datos de ejemplo).
- Sumar tests automáticos (Feature/Unit) que cubran al menos el flujo de reserva, los permisos por rol y la lógica de horarios disponibles.
- Integrar una pasarela de pagos real para los turnos "online".
- Agregar un archivo `.env.example` con todas las variables necesarias documentadas.
- Evaluar mover la autorización a Policies/Gates nativas de Laravel para reducir código a mano y mejorar la mantenibilidad.

---

## 📄 Licencia

Este proyecto está construido sobre el framework Laravel, que se distribuye bajo licencia [MIT](https://opensource.org/licenses/MIT). El código propio de Trimly no declara una licencia específica en este repositorio.
