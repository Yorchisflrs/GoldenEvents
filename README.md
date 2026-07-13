# Golden Hour Events

Golden Hour Events es una plataforma web para planificar eventos sociales mediante catalogo de servicios y solicitudes de cotizacion.

## Tecnologias

- PHP puro
- MySQL
- HTML
- CSS
- JavaScript basico
- XAMPP
- phpMyAdmin

## Instrucciones para probar

1. Activar Apache desde el panel de XAMPP.
2. Verificar que el proyecto este ubicado en `C:\xampp\htdocs\GoldenHoursEvents`.
3. Abrir en el navegador: `http://localhost/GoldenHoursEvents/`.

## Estado actual

Parte 1 completada: estructura base, vistas minimas, navegacion basica y archivos preparados para futuras fases.

## Parte 2: Base de Datos

Base de datos creada:
golden_hour_events

Tablas:
- roles
- usuarios
- eventos
- reservas
- pagos
- proveedores
- servicios
- traducciones

Los usuarios demo existentes se conservan activos para pruebas locales. Sus credenciales no se publican en la interfaz web ni en esta guia.

Archivo SQL:
sql/database.sql

Prueba de conexion disponible solo en desarrollo y para administradores autenticados:
http://localhost/GoldenHoursEvents/test_connection.php

## Parte 3: Lógica funcional

Incluye:
- Login real
- Registro real
- Logout
- Roles
- CRUD de eventos internos
- Reservas y pagos como modulo futuro heredado
- Panel de administrador
- Gestión básica de proveedor

Los cuatro roles demo se mantienen para validar permisos en el entorno local.

Rutas principales:
- /views/auth/login.php
- /views/auth/register.php
- /views/client/events.php
- /views/organizer/my_events.php
- /views/admin/dashboard.php

## Parte 3 Corregida: Marketplace y Cotizador

La version 1 del sistema no vende entradas.
El cliente puede explorar servicios y armar su evento personalizado.

Funcionalidades:
- Catalogo publico de servicios.
- Filtro por categoria.
- Servicios con imagen.
- Proveedor registra servicios.
- Cliente arma evento personalizado.
- Cliente calcula costo estimado.
- Cliente envia solicitud de cotizacion.
- Admin gestiona cotizaciones.

Rutas:
- /views/client/services.php
- /views/client/service_detail.php
- /views/client/build_event.php
- /views/client/quote_result.php
- /views/client/my_quotes.php
- /views/provider/create_service.php
- /views/provider/my_services.php
- /views/admin/quotes.php
- /views/admin/services.php

SQL:
- sql/upgrade_event_builder.sql

## Parte 4: Diseño visual responsive

Se implementó:
- CSS profesional mobile-first.
- Navbar responsive.
- Hero principal.
- Tarjetas modernas.
- Formularios mejorados.
- Tablas profesionales.
- Badges de estado.
- Transiciones suaves.
- Placeholders visuales.
- Diseño adaptable a móvil, tablet y escritorio.

## Parte 4.1: UI Premium y Transiciones

Mejoras:
- Hero Golden Hour full responsive.
- Fondos degradados.
- Tarjetas modernas.
- Transiciones con History API.
- Animaciones suaves.
- Navbar responsive.
- Placeholders visuales.
- Accesibilidad con prefers-reduced-motion.
- SEO básico.
- Lazy loading en imágenes.
- Diseño mobile-first.

## Configuracion y migraciones

La configuracion central se encuentra en `config/app.php` y admite variables de entorno. Los nombres y valores locales de referencia estan en `.env.example`; el proyecto no carga archivos `.env` ni requiere Composer.

Valores locales compatibles con XAMPP:

- `APP_ENV=development`
- `APP_BASE_PATH=/GoldenHoursEvents`
- `DB_HOST=localhost`
- `DB_NAME=golden_hour_events`
- `DB_USER=root`
- `DB_PASS=`

Antes de migrar una base con datos se debe crear un respaldo externo. Para consultar el estado:

```powershell
C:\xampp\php\php.exe sql\migrate.php --status
```

No ejecutes `sql/database.sql` sobre una base existente: es un script de instalacion limpia y contiene operaciones destructivas.
