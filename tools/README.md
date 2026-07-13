# Herramientas operativas

## Finalización de eventos vencidos

Ejecutar periódicamente desde el Programador de tareas de Windows con PHP de XAMPP:

```powershell
C:\xampp\php\php.exe C:\xampp\htdocs\GoldenHoursEvents\tools\finalize_events.php
```

El comando es idempotente: cambia a `finalizado` los eventos no terminales cuya fecha final ya pasó; si no tienen fecha final, utiliza la fecha inicial. Las consultas GET del catálogo no modifican datos.
