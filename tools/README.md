# Herramientas operativas

## Finalización de eventos vencidos

Ejecutar periódicamente desde el Programador de tareas de Windows con PHP de XAMPP:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\GoldenHoursEvents\tools\finalize_events.php
```

El comando es idempotente: cambia a `finalizado` los eventos no terminales cuya fecha final ya pasó; si no tienen fecha final, utiliza la fecha inicial. Las consultas GET del catálogo no modifican datos.

## Vencimiento de reservas pendientes

Desde la raíz del proyecto en PowerShell:

```powershell
C:\xampp\php\php.exe tools\expire_reservations.php
```

El script solo cambia a `vencida` las reservas `pendiente_pago` cuyo plazo terminó; no elimina registros y es idempotente. En el Programador de tareas de Windows configure `C:\xampp\php\php.exe` como programa, `tools\expire_reservations.php` como argumento y `C:\xampp\htdocs\GoldenHoursEvents` como directorio inicial. Puede ejecutarse cada cinco minutos. El cálculo de aforo ignora los plazos vencidos incluso antes de que se ejecute esta tarea.
