# Generador de documentación

`FFFF.js` es una utilidad auxiliar para generar un documento DOCX de especificación. No forma parte del runtime PHP ni es cargado por Apache, los controladores o las vistas de Golden Hour Events.

Consideraciones:

- Requiere Node.js y el paquete externo `docx`.
- Conserva una ruta de salida histórica (`/mnt/user-data/outputs/`) que no es portable a Windows.
- No debe ejecutarse en producción.
- La Parte 1 no instala Node.js, `docx` ni ninguna otra dependencia.
- Los documentos generados se consideran artefactos locales y están excluidos por `.gitignore`.
