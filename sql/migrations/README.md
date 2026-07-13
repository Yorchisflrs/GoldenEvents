# Migraciones de Golden Hour Events

Las migraciones se aplican en orden lexical y se registran con checksum SHA-256 en `schema_migrations`.

Comandos desde la raíz del proyecto:

```powershell
C:\xampp\php\php.exe sql\migrate.php --status
C:\xampp\php\php.exe sql\migrate.php --dry-run
C:\xampp\php\php.exe sql\migrate.php
```

Reglas:

- Crear un respaldo verificado antes de aplicar migraciones.
- No modificar una migración ya registrada; crear la siguiente numeración.
- No ejecutar `sql/database.sql` sobre una base con datos.
- Separar sentencias múltiples con una línea `-- statement-break`.
- El ejecutor es exclusivo de CLI, usa un bloqueo de MySQL y rechaza checksums alterados.
