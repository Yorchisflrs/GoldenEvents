const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, HeadingLevel, BorderStyle, WidthType,
  ShadingType, VerticalAlign, PageNumber, PageBreak, LevelFormat,
  TableOfContents, UnderlineType
} = require('docx');
const fs = require('fs');

const GOLD = "B8860B";
const DARK_GOLD = "8B6914";
const LIGHT_GOLD = "FFF8DC";
const HEADER_BG = "2C2C2C";
const TABLE_HEADER_BG = "D4A017";

const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };
const goldBorder = { style: BorderStyle.SINGLE, size: 2, color: GOLD };
const goldBorders = { top: goldBorder, bottom: goldBorder, left: goldBorder, right: goldBorder };

function heading1(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 360, after: 180 },
    children: [new TextRun({ text, bold: true, size: 32, font: "Arial", color: DARK_GOLD })]
  });
}

function heading2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 240, after: 120 },
    children: [new TextRun({ text, bold: true, size: 26, font: "Arial", color: HEADER_BG })]
  });
}

function heading3(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_3,
    spacing: { before: 180, after: 80 },
    children: [new TextRun({ text, bold: true, size: 24, font: "Arial", color: DARK_GOLD })]
  });
}

function para(text, opts = {}) {
  return new Paragraph({
    spacing: { before: 60, after: 60 },
    alignment: opts.center ? AlignmentType.CENTER : AlignmentType.JUSTIFIED,
    children: [new TextRun({ text, font: "Arial", size: 22, bold: opts.bold, italic: opts.italic, color: opts.color })]
  });
}

function bullet(text, level = 0) {
  return new Paragraph({
    numbering: { reference: "bullets", level },
    spacing: { before: 40, after: 40 },
    children: [new TextRun({ text, font: "Arial", size: 22 })]
  });
}

function numbered(text, level = 0) {
  return new Paragraph({
    numbering: { reference: "numbers", level },
    spacing: { before: 60, after: 60 },
    children: [new TextRun({ text, font: "Arial", size: 22 })]
  });
}

function pageBreak() {
  return new Paragraph({ children: [new PageBreak()] });
}

function spacer() {
  return new Paragraph({ spacing: { before: 60, after: 60 }, children: [new TextRun("")] });
}

function simpleCell(text, opts = {}) {
  return new TableCell({
    borders: opts.noBorder ? undefined : (opts.gold ? goldBorders : borders),
    width: { size: opts.width || 4680, type: WidthType.DXA },
    shading: opts.bg ? { fill: opts.bg, type: ShadingType.CLEAR } : undefined,
    verticalAlign: VerticalAlign.CENTER,
    margins: { top: 80, bottom: 80, left: 120, right: 120 },
    children: [new Paragraph({
      alignment: opts.center ? AlignmentType.CENTER : AlignmentType.LEFT,
      children: [new TextRun({
        text, font: "Arial", size: opts.size || 20,
        bold: opts.bold || false, color: opts.textColor || "000000"
      })]
    })]
  });
}

function headerRow(texts, widths) {
  return new TableRow({
    tableHeader: true,
    children: texts.map((t, i) => new TableCell({
      borders: goldBorders,
      width: { size: widths[i], type: WidthType.DXA },
      shading: { fill: TABLE_HEADER_BG, type: ShadingType.CLEAR },
      verticalAlign: VerticalAlign.CENTER,
      margins: { top: 80, bottom: 80, left: 120, right: 120 },
      children: [new Paragraph({
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: t, font: "Arial", size: 20, bold: true, color: "FFFFFF" })]
      })]
    }))
  });
}

// Case of use table helper
function cuTable(rows) {
  const W = 9360;
  const COL1 = 2200;
  const COL2 = W - COL1;
  return new Table({
    width: { size: W, type: WidthType.DXA },
    columnWidths: [COL1, COL2],
    rows: rows.map((r, i) => new TableRow({
      children: [
        new TableCell({
          borders: goldBorders,
          width: { size: COL1, type: WidthType.DXA },
          shading: { fill: i === 0 ? TABLE_HEADER_BG : "FFF9ED", type: ShadingType.CLEAR },
          margins: { top: 80, bottom: 80, left: 120, right: 120 },
          children: [new Paragraph({
            children: [new TextRun({ text: r[0], font: "Arial", size: 20, bold: true, color: i === 0 ? "FFFFFF" : DARK_GOLD })]
          })]
        }),
        new TableCell({
          borders: goldBorders,
          width: { size: COL2, type: WidthType.DXA },
          margins: { top: 80, bottom: 80, left: 120, right: 120 },
          children: Array.isArray(r[1]) ? r[1] : [new Paragraph({
            children: [new TextRun({ text: r[1], font: "Arial", size: 20, color: i === 0 ? "FFFFFF" : "000000", bold: i === 0 })]
          })]
        })
      ]
    }))
  });
}

function cuSection(title, rows) {
  return [spacer(), heading3(title), spacer(), cuTable(rows), spacer()];
}

function multiPara(lines) {
  return lines.map(l => new Paragraph({ spacing: { before: 40, after: 40 }, children: [new TextRun({ text: l, font: "Arial", size: 20 })] }));
}

// ====================== DOCUMENT ======================
const doc = new Document({
  numbering: {
    config: [
      {
        reference: "bullets",
        levels: [{
          level: 0, format: LevelFormat.BULLET, text: "\u2022", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } }
        }, {
          level: 1, format: LevelFormat.BULLET, text: "-", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 1080, hanging: 360 } } }
        }]
      },
      {
        reference: "numbers",
        levels: [{
          level: 0, format: LevelFormat.DECIMAL, text: "%1.", alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } }
        }]
      }
    ]
  },
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } },
    paragraphStyles: [
      {
        id: "Heading1", name: "Heading 1", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 32, bold: true, font: "Arial", color: DARK_GOLD },
        paragraph: { spacing: { before: 360, after: 180 }, outlineLevel: 0 }
      },
      {
        id: "Heading2", name: "Heading 2", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 26, bold: true, font: "Arial", color: HEADER_BG },
        paragraph: { spacing: { before: 240, after: 120 }, outlineLevel: 1 }
      },
      {
        id: "Heading3", name: "Heading 3", basedOn: "Normal", next: "Normal", quickFormat: true,
        run: { size: 24, bold: true, font: "Arial", color: DARK_GOLD },
        paragraph: { spacing: { before: 180, after: 80 }, outlineLevel: 2 }
      }
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: 12240, height: 15840 },
        margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 }
      }
    },
    headers: {
      default: new Header({
        children: [
          new Paragraph({
            border: { bottom: { style: BorderStyle.SINGLE, size: 6, color: GOLD } },
            spacing: { after: 100 },
            children: [
              new TextRun({ text: "Especificaci\xF3n de Requisitos de Software - Golden Hour Events", font: "Arial", size: 18, color: "666666", italic: true }),
            ]
          })
        ]
      })
    },
    footers: {
      default: new Footer({
        children: [
          new Paragraph({
            border: { top: { style: BorderStyle.SINGLE, size: 4, color: GOLD } },
            spacing: { before: 80 },
            alignment: AlignmentType.CENTER,
            children: [
              new TextRun({ text: "Universidad Nacional del Altiplano - FINESI  |  P\xE1gina ", font: "Arial", size: 18, color: "666666" }),
              new TextRun({ children: [PageNumber.CURRENT], font: "Arial", size: 18, color: "666666" }),
              new TextRun({ text: " de ", font: "Arial", size: 18, color: "666666" }),
              new TextRun({ children: [PageNumber.TOTAL_PAGES], font: "Arial", size: 18, color: "666666" }),
            ]
          })
        ]
      })
    },
    children: [
      // ============ COVER PAGE ============
      spacer(), spacer(), spacer(),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 240, after: 120 },
        children: [new TextRun({ text: "UNIVERSIDAD NACIONAL DEL ALTIPLANO", font: "Arial", size: 28, bold: true, color: DARK_GOLD })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 60, after: 240 },
        children: [new TextRun({ text: "Facultad de Ingenier\xEDa Estad\xEDstica e Inform\xE1tica - FINESI", font: "Arial", size: 22, color: "444444" })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        border: {
          top: { style: BorderStyle.SINGLE, size: 8, color: GOLD },
          bottom: { style: BorderStyle.SINGLE, size: 8, color: GOLD }
        },
        spacing: { before: 240, after: 240 },
        children: [
          new TextRun({ text: "\n", font: "Arial", size: 22 }),
          new TextRun({ text: "Especificaci\xF3n de Requisitos de Software", font: "Arial", size: 36, bold: true, color: HEADER_BG }),
          new TextRun({ break: 1 }),
          new TextRun({ text: "Golden Hour Events", font: "Arial", size: 44, bold: true, color: DARK_GOLD }),
          new TextRun({ break: 1 }),
          new TextRun({ text: "Plataforma web para planificaci\xF3n de eventos personalizados", font: "Arial", size: 24, italic: true, color: "555555" }),
          new TextRun({ break: 1 }),
        ]
      }),
      spacer(),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 120, after: 60 },
        children: [new TextRun({ text: "Versi\xF3n 4.0", font: "Arial", size: 26, bold: true, color: DARK_GOLD })]
      }),
      spacer(), spacer(),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 60, after: 40 },
        children: [new TextRun({ text: "Autores:", font: "Arial", size: 22, bold: true })]
      }),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 40, after: 40 },
        children: [new TextRun({ text: "Jorge Flores, Yulissa Etzel, Edilberto Mamani y Belinda Apaza", font: "Arial", size: 22 })]
      }),
      spacer(),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 60, after: 40 },
        children: [new TextRun({ text: "2026", font: "Arial", size: 22, bold: true, color: "444444" })]
      }),
      pageBreak(),

      // ============ REVISION HISTORY ============
      heading1("Historial de Revisiones"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [720, 1440, 1200, 4200, 1800],
        rows: [
          headerRow(["Item", "Fecha", "Versi\xF3n", "Descripci\xF3n", "Responsable"], [720, 1440, 1200, 4200, 1800]),
          new TableRow({ children: [
            simpleCell("1", { width: 720, center: true }),
            simpleCell("08/05/2026", { width: 1440, center: true }),
            simpleCell("1.0", { width: 1200, center: true }),
            simpleCell("Versi\xF3n inicial del documento ERS para Golden Hour Events.", { width: 4200 }),
            simpleCell("Equipo de Desarrollo", { width: 1800 })
          ]}),
          new TableRow({ children: [
            simpleCell("2", { width: 720, center: true, bg: "FFFBF0" }),
            simpleCell("09/05/2026", { width: 1440, center: true, bg: "FFFBF0" }),
            simpleCell("2.0", { width: 1200, center: true, bg: "FFFBF0" }),
            simpleCell("Definici\xF3n del enfoque de marketplace de servicios y cotizador de eventos personalizados.", { width: 4200, bg: "FFFBF0" }),
            simpleCell("Jorge Flores, Yulissa Etzel", { width: 1800, bg: "FFFBF0" })
          ]}),
          new TableRow({ children: [
            simpleCell("3", { width: 720, center: true }),
            simpleCell("10/05/2026", { width: 1440, center: true }),
            simpleCell("3.0", { width: 1200, center: true }),
            simpleCell("Actualizaci\xF3n de actores, requisitos, casos de uso y modelo de datos.", { width: 4200 }),
            simpleCell("Edilberto Mamani, Belinda Apaza", { width: 1800 })
          ]}),
          new TableRow({ children: [
            simpleCell("4", { width: 720, center: true, bg: "FFFBF0" }),
            simpleCell("11/05/2026", { width: 1440, center: true, bg: "FFFBF0" }),
            simpleCell("4.0", { width: 1200, center: true, bg: "FFFBF0" }),
            simpleCell("Correcci\xF3n final: se excluye la venta de entradas en la versi\xF3n 1 y se prioriza la cotizaci\xF3n personalizada.", { width: 4200, bg: "FFFBF0" }),
            simpleCell("Equipo de Desarrollo", { width: 1800, bg: "FFFBF0" })
          ]}),
        ]
      }),
      pageBreak(),

      // ============ TABLE OF CONTENTS ============
      heading1("Tabla de Contenido"),
      new TableOfContents("Tabla de Contenido", {
        hyperlink: true,
        headingStyleRange: "1-3",
      }),
      pageBreak(),

      // ============ SECTION 1 ============
      heading1("1. Introducci\xF3n"),
      para("Este documento constituye la Especificaci\xF3n de Requisitos de Software (ERS) del proyecto Golden Hour Events. El documento ha sido elaborado tomando como referencia la estructura del est\xE1ndar IEEE Recommended Practice for Software Requirements Specification IEEE Std 830-1998 y adapt\xE1ndolo al contexto funcional del sistema desarrollado."),
      spacer(),
      para("Golden Hour Events es una plataforma web orientada a la planificaci\xF3n de eventos personalizados. La versi\xF3n 1 del sistema no contempla venta de entradas ni tickets; su prop\xF3sito central es permitir que los clientes exploren servicios, seleccionen componentes para su evento y soliciten una cotizaci\xF3n estimada. Los servicios incluyen locales, decoraci\xF3n, DJ y m\xFAsica, animadores, tortas, catering, fotograf\xEDa, video, mesas, sillas y seguridad."),

      heading2("1.1 Prop\xF3sito"),
      para("El prop\xF3sito de este documento es describir de forma clara, completa y verificable los requisitos funcionales y no funcionales del sistema Golden Hour Events. Tambi\xE9n se especifican los actores, casos de uso, reglas de negocio, interfaces, dependencias tecnol\xF3gicas y restricciones bajo las cuales se desarrollar\xE1 el software."),
      spacer(),
      para("La ERS servir\xE1 como base para la implementaci\xF3n, validaci\xF3n, pruebas y mantenimiento del sistema, asegurando que el desarrollo se mantenga alineado con el objetivo principal: facilitar la planificaci\xF3n integral de eventos personalizados mediante un cat\xE1logo digital de servicios y un cotizador en l\xEDnea."),

      heading2("1.2 \xC1mbito del Sistema"),
      para("El sistema se denominar\xE1 Golden Hour Events y funcionar\xE1 como una plataforma web para conectar clientes interesados en organizar eventos con proveedores de servicios. La plataforma permitir\xE1 consultar servicios disponibles, filtrarlos por categor\xEDa, revisar detalles, armar un evento personalizado, calcular un costo aproximado y enviar una solicitud formal de cotizaci\xF3n."),
      spacer(),
      para("El alcance de la versi\xF3n 1 incluye gesti\xF3n de usuarios, gesti\xF3n de proveedores, publicaci\xF3n de servicios, visualizaci\xF3n p\xFAblica del cat\xE1logo, construcci\xF3n de cotizaciones y administraci\xF3n de solicitudes. La venta de entradas, pagos reales en l\xEDnea y emisi\xF3n de tickets quedan fuera del alcance de esta versi\xF3n y se consideran posibles ampliaciones futuras."),

      heading2("1.3 Definiciones, Acr\xF3nimos y Abreviaturas"),
      heading3("1.3.1 Definiciones"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2200, 7160],
        rows: [
          headerRow(["T\xE9rmino", "Definici\xF3n"], [2200, 7160]),
          new TableRow({ children: [simpleCell("Actor", { width: 2200, bold: true }), simpleCell("Persona, rol o entidad externa que interact\xFAa con el sistema.", { width: 7160 })] }),
          new TableRow({ children: [simpleCell("Cliente", { width: 2200, bold: true, bg: "FFFBF0" }), simpleCell("Usuario que explora servicios, arma un evento personalizado y solicita una cotizaci\xF3n.", { width: 7160, bg: "FFFBF0" })] }),
          new TableRow({ children: [simpleCell("Proveedor", { width: 2200, bold: true }), simpleCell("Usuario que registra servicios relacionados con eventos: local, decoraci\xF3n, DJ, catering, fotograf\xEDa, entre otros.", { width: 7160 })] }),
          new TableRow({ children: [simpleCell("Administrador", { width: 2200, bold: true, bg: "FFFBF0" }), simpleCell("Usuario con permisos para supervisar usuarios, servicios, cotizaciones y reportes.", { width: 7160, bg: "FFFBF0" })] }),
          new TableRow({ children: [simpleCell("Servicio", { width: 2200, bold: true }), simpleCell("Elemento ofrecido por un proveedor para formar parte de un evento personalizado.", { width: 7160 })] }),
          new TableRow({ children: [simpleCell("Cotizaci\xF3n", { width: 2200, bold: true, bg: "FFFBF0" }), simpleCell("Solicitud generada por un cliente que agrupa varios servicios seleccionados y calcula un costo estimado.", { width: 7160, bg: "FFFBF0" })] }),
          new TableRow({ children: [simpleCell("Categor\xEDa de servicio", { width: 2200, bold: true }), simpleCell("Clasificaci\xF3n del servicio: local, decoraci\xF3n, DJ y m\xFAsica, torta, catering, fotograf\xEDa y video, etc.", { width: 7160 })] }),
          new TableRow({ children: [simpleCell("Caso de uso", { width: 2200, bold: true, bg: "FFFBF0" }), simpleCell("Secuencia de acciones que realiza un actor con el sistema para obtener un resultado observable.", { width: 7160, bg: "FFFBF0" })] }),
        ]
      }),

      spacer(),
      heading3("1.3.2 Acr\xF3nimos"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2200, 7160],
        rows: [
          headerRow(["Acr\xF3nimo", "Significado"], [2200, 7160]),
          new TableRow({ children: [simpleCell("ERS", { width: 2200, bold: true }), simpleCell("Especificaci\xF3n de Requisitos de Software", { width: 7160 })] }),
          new TableRow({ children: [simpleCell("UML", { width: 2200, bold: true, bg: "FFFBF0" }), simpleCell("Lenguaje Unificado de Modelado", { width: 7160, bg: "FFFBF0" })] }),
          new TableRow({ children: [simpleCell("CRUD", { width: 2200, bold: true }), simpleCell("Crear, Leer, Actualizar y Eliminar", { width: 7160 })] }),
          new TableRow({ children: [simpleCell("PDO", { width: 2200, bold: true, bg: "FFFBF0" }), simpleCell("PHP Data Objects, extensi\xF3n de PHP para acceso seguro a bases de datos", { width: 7160, bg: "FFFBF0" })] }),
          new TableRow({ children: [simpleCell("SQL", { width: 2200, bold: true }), simpleCell("Structured Query Language", { width: 7160 })] }),
          new TableRow({ children: [simpleCell("UI", { width: 2200, bold: true, bg: "FFFBF0" }), simpleCell("Interfaz de Usuario", { width: 7160, bg: "FFFBF0" })] }),
          new TableRow({ children: [simpleCell("UX", { width: 2200, bold: true }), simpleCell("Experiencia de Usuario", { width: 7160 })] }),
        ]
      }),

      heading2("1.4 Referencias"),
      bullet("IEEE Std 830-1998: Recommended Practice for Software Requirements Specification."),
      bullet("Cat\xE1logo de requisitos del proyecto Golden Hour Events."),
      bullet("Mapeo de requerimientos y casos de uso del sistema Golden Hour Events."),
      bullet("Documentaci\xF3n interna del desarrollo en PHP puro, MySQL, XAMPP y Visual Studio Code."),
      bullet("Modelo de base de datos definido para usuarios, roles, proveedores, categor\xEDas, servicios, cotizaciones y detalles de cotizaci\xF3n."),

      heading2("1.5 Visi\xF3n General de la ERS"),
      para("El documento se organiza en tres secciones principales. La primera secci\xF3n presenta el prop\xF3sito, alcance, definiciones, acr\xF3nimos y referencias. La segunda secci\xF3n describe el sistema desde una perspectiva general, incluyendo actores, modelo de casos de uso, caracter\xEDsticas de los usuarios, suposiciones y dependencias. La tercera secci\xF3n contiene los requisitos espec\xEDficos, la especificaci\xF3n detallada de casos de uso, interfaces y requisitos tecnol\xF3gicos."),
      pageBreak(),

      // ============ SECTION 2 ============
      heading1("2. Descripci\xF3n General"),
      para("Golden Hour Events busca resolver un problema frecuente en la organizaci\xF3n de eventos: la necesidad de buscar por separado locales, decoraci\xF3n, DJ, tortas, catering, fotograf\xEDa y otros servicios. La plataforma concentra estas opciones en un cat\xE1logo digital y permite que el cliente seleccione los componentes necesarios para estimar el costo de su evento."),
      spacer(),
      para("El sistema est\xE1 dise\xF1ado para usuarios con conocimientos b\xE1sicos de navegaci\xF3n web. La interfaz debe ser clara, responsive y accesible, permitiendo el uso desde computadora, tablet o celular. La experiencia se enfoca en mostrar informaci\xF3n visual de los servicios, facilitar la comparaci\xF3n y reducir el esfuerzo de planificaci\xF3n."),

      heading2("2.1 Modelo de Casos de Uso"),
      para("El modelo de casos de uso representa las funcionalidades principales del sistema desde la perspectiva de los actores. En esta versi\xF3n se priorizan la exploraci\xF3n de servicios, la creaci\xF3n de cotizaciones personalizadas, la gesti\xF3n de servicios por proveedores y la administraci\xF3n general del sistema."),

      heading3("2.1.1 Cat\xE1logo de Actores"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2400, 3760, 3200],
        rows: [
          headerRow(["Actor", "Descripci\xF3n", "Funciones principales"], [2400, 3760, 3200]),
          new TableRow({ children: [
            simpleCell("Cliente / Visitante", { width: 2400, bold: true }),
            simpleCell("Persona interesada en organizar un evento. Puede explorar servicios sin iniciar sesi\xF3n.", { width: 3760 }),
            simpleCell("Ver servicios, filtrar categor\xEDas, ver detalle, armar evento, enviar cotizaci\xF3n.", { width: 3200 })
          ]}),
          new TableRow({ children: [
            simpleCell("Cliente registrado", { width: 2400, bold: true, bg: "FFFBF0" }),
            simpleCell("Cliente que crea una cuenta para consultar posteriormente sus cotizaciones.", { width: 3760, bg: "FFFBF0" }),
            simpleCell("Iniciar sesi\xF3n, ver sus cotizaciones, generar solicitudes asociadas a su usuario.", { width: 3200, bg: "FFFBF0" })
          ]}),
          new TableRow({ children: [
            simpleCell("Proveedor", { width: 2400, bold: true }),
            simpleCell("Persona o empresa que ofrece servicios para eventos.", { width: 3760 }),
            simpleCell("Registrar servicios, cargar im\xE1genes, indicar precio, capacidad, ubicaci\xF3n y disponibilidad.", { width: 3200 })
          ]}),
          new TableRow({ children: [
            simpleCell("Administrador", { width: 2400, bold: true, bg: "FFFBF0" }),
            simpleCell("Responsable de supervisar la plataforma.", { width: 3760, bg: "FFFBF0" }),
            simpleCell("Gestionar usuarios, servicios, cotizaciones, estados y reportes.", { width: 3200, bg: "FFFBF0" })
          ]}),
          new TableRow({ children: [
            simpleCell("Sistema", { width: 2400, bold: true }),
            simpleCell("Componente interno que ejecuta validaciones y c\xE1lculos.", { width: 3760 }),
            simpleCell("Validar datos, calcular total estimado, registrar cotizaciones y proteger accesos.", { width: 3200 })
          ]}),
        ]
      }),
      spacer(),
      para("Caracter\xEDsticas esperadas de los usuarios: los usuarios deben tener conocimiento b\xE1sico de navegaci\xF3n web, uso de formularios y acceso a internet. La plataforma debe reducir la complejidad mediante men\xFAs claros, formularios guiados, textos entendibles y tarjetas visuales de servicios.", { italic: true }),

      heading3("2.1.2 Diagrama de Casos de Uso"),
      para("La Figura 1 representa el diagrama general de casos de uso de la versi\xF3n 1 de Golden Hour Events. El sistema se centra en la planificaci\xF3n y cotizaci\xF3n personalizada de eventos, no en la venta de entradas."),
      spacer(),
      new Paragraph({
        alignment: AlignmentType.CENTER,
        border: { style: BorderStyle.SINGLE, size: 4, color: GOLD },
        spacing: { before: 120, after: 120 },
        children: [new TextRun({ text: "[Diagrama de Casos de Uso - Ver archivo de diagramas adjunto]", font: "Arial", size: 20, italic: true, color: "888888" })]
      }),
      new Paragraph({ alignment: AlignmentType.CENTER, spacing: { before: 60, after: 120 }, children: [new TextRun({ text: "Fig. 1 Diagrama de Casos de Uso del Sistema Golden Hour Events", font: "Arial", size: 18, italic: true, bold: true })] }),
      spacer(),
      para("El flujo principal inicia cuando el cliente explora el cat\xE1logo de servicios y selecciona los elementos que formar\xE1n parte de su evento. Luego el sistema calcula el costo estimado y registra la solicitud de cotizaci\xF3n para que el equipo de Golden Hour Events pueda contactar al cliente."),

      heading2("2.2 Suposiciones y Dependencias"),
      heading3("2.2.1 Suposiciones"),
      bullet("El sistema ser\xE1 utilizado inicialmente en un entorno local con XAMPP y posteriormente podr\xE1 desplegarse en un servidor web."),
      bullet("Los proveedores contar\xE1n con informaci\xF3n b\xE1sica de sus servicios: nombre, categor\xEDa, descripci\xF3n, precio, capacidad, ubicaci\xF3n e imagen."),
      bullet("Los clientes podr\xE1n generar cotizaciones sin iniciar sesi\xF3n; si se autentican, podr\xE1n consultar su historial de cotizaciones."),
      bullet("La versi\xF3n 1 no realizar\xE1 cobros en l\xEDnea ni vender\xE1 entradas. La confirmaci\xF3n final del evento ser\xE1 gestionada administrativamente."),
      bullet("Las im\xE1genes deber\xE1n optimizarse para web y almacenarse en rutas internas del proyecto."),

      heading3("2.2.2 Dependencias"),
      bullet("El sistema depende de Apache y MySQL ejecut\xE1ndose desde XAMPP durante el desarrollo local."),
      bullet("La aplicaci\xF3n depende de PHP con soporte para PDO y extensiones b\xE1sicas de manejo de archivos."),
      bullet("La disponibilidad del sistema depender\xE1 del servidor web donde sea desplegado."),
      bullet("La base de datos MySQL debe mantener integridad referencial entre roles, usuarios, proveedores, servicios y cotizaciones."),
      bullet("La carga de im\xE1genes depende de permisos de escritura en las carpetas p\xFAblicas de uploads."),
      pageBreak(),

      // ============ SECTION 3 ============
      heading1("3. Requisitos Espec\xEDficos"),
      para("Los requisitos espec\xEDficos definen el comportamiento esperado del sistema. Se dividen en requisitos funcionales, requisitos no funcionales y especificaci\xF3n de casos de uso. Cada requisito se redacta de manera verificable para facilitar pruebas y validaci\xF3n."),

      heading2("3.1 Requisitos Funcionales"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [1080, 2200, 4280, 1000, 800],
        rows: [
          headerRow(["C\xF3digo", "Nombre", "Descripci\xF3n", "Prioridad", "Actor"], [1080, 2200, 4280, 1000, 800]),
          ...[
            ["RF-01","Registrar usuario","El sistema debe permitir registrar clientes y proveedores con datos b\xE1sicos y contrase\xF1a segura.","Alta","Cliente / Proveedor"],
            ["RF-02","Validar usuario","El sistema debe permitir iniciar sesi\xF3n mediante correo y contrase\xF1a.","Alta","Todos"],
            ["RF-03","Cerrar sesi\xF3n","El sistema debe permitir finalizar la sesi\xF3n activa de manera segura.","Alta","Todos"],
            ["RF-04","Ver cat\xE1logo de servicios","El sistema debe mostrar servicios p\xFAblicos sin requerir login.","Alta","Cliente / Visitante"],
            ["RF-05","Filtrar servicios","El sistema debe permitir filtrar servicios por categor\xEDa.","Alta","Cliente / Visitante"],
            ["RF-06","Ver detalle de servicio","El sistema debe mostrar informaci\xF3n completa de un servicio: imagen, precio, capacidad, ubicaci\xF3n y proveedor.","Alta","Cliente / Visitante"],
            ["RF-07","Registrar servicio","El proveedor debe poder registrar servicios con categor\xEDa, precio, capacidad, ubicaci\xF3n e imagen.","Alta","Proveedor"],
            ["RF-08","Consultar servicios propios","El proveedor debe visualizar los servicios que registr\xF3.","Alta","Proveedor"],
            ["RF-09","Armar evento personalizado","El cliente debe poder seleccionar varios servicios para construir su evento.","Alta","Cliente / Visitante"],
            ["RF-10","Calcular costo estimado","El sistema debe calcular el total de la cotizaci\xF3n con base en los servicios seleccionados.","Alta","Sistema"],
            ["RF-11","Enviar solicitud de cotizaci\xF3n","El cliente debe poder registrar una solicitud de cotizaci\xF3n con datos de contacto y evento.","Alta","Cliente / Visitante"],
            ["RF-12","Consultar mis cotizaciones","El cliente registrado debe visualizar sus cotizaciones asociadas.","Media","Cliente registrado"],
            ["RF-13","Gestionar cotizaciones","El administrador debe visualizar y cambiar estado de cotizaciones.","Alta","Administrador"],
            ["RF-14","Gestionar servicios","El administrador debe revisar servicios registrados y su estado.","Alta","Administrador"],
            ["RF-15","Gestionar usuarios","El administrador debe visualizar usuarios registrados y su rol.","Media","Administrador"],
            ["RF-16","Mostrar panel por rol","El sistema debe redirigir a cada usuario a su panel seg\xFAn su rol.","Alta","Sistema"],
            ["RF-17","Subir im\xE1genes","El sistema debe permitir subir im\xE1genes v\xE1lidas para servicios.","Media","Proveedor"],
            ["RF-18","Validar archivos","El sistema debe rechazar archivos no permitidos o peligrosos.","Alta","Sistema"],
            ["RF-19","Generar reportes","El administrador debe visualizar reportes b\xE1sicos de usuarios, servicios y cotizaciones.","Media","Administrador"],
            ["RF-20","Dise\xF1o responsive","El sistema debe visualizarse correctamente en m\xF3viles, tablets y escritorio.","Alta","Todos"],
          ].map((r, i) => new TableRow({ children: [
            simpleCell(r[0], { width: 1080, center: true, bg: i % 2 === 1 ? "FFFBF0" : undefined, bold: true }),
            simpleCell(r[1], { width: 2200, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[2], { width: 4280, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[3], { width: 1000, center: true, bg: i % 2 === 1 ? "FFFBF0" : undefined,
              textColor: r[3] === "Alta" ? "C0392B" : "E67E22" }),
            simpleCell(r[4], { width: 800, bg: i % 2 === 1 ? "FFFBF0" : undefined })
          ]}))
        ]
      }),

      spacer(),
      heading2("3.2 Requisitos No Funcionales"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [1080, 1800, 5480, 1000],
        rows: [
          headerRow(["C\xF3digo", "Categor\xEDa", "Descripci\xF3n", "Prioridad"], [1080, 1800, 5480, 1000]),
          ...[
            ["RNF-01","Seguridad","Las contrase\xF1as deben almacenarse con password_hash y verificarse con password_verify.","Alta"],
            ["RNF-02","Acceso a datos","Todas las consultas a base de datos deben realizarse mediante PDO y consultas preparadas.","Alta"],
            ["RNF-03","Usabilidad","La interfaz debe ser clara, entendible y orientada a usuarios no t\xE9cnicos.","Alta"],
            ["RNF-04","Responsive","El sistema debe adaptarse a pantallas de 360px, tablets y escritorio.","Alta"],
            ["RNF-05","Rendimiento","Las p\xE1ginas principales deben cargar en un tiempo razonable bajo entorno local y servidor est\xE1ndar.","Media"],
            ["RNF-06","Accesibilidad","Debe existir contraste adecuado, foco visible y texto alternativo en im\xE1genes importantes.","Media"],
            ["RNF-07","Mantenibilidad","El c\xF3digo debe estar organizado en carpetas: config, controllers, models, views, includes y public.","Alta"],
            ["RNF-08","Compatibilidad","Debe funcionar en navegadores modernos como Chrome, Edge y Firefox.","Media"],
            ["RNF-09","Escalabilidad","La arquitectura debe permitir integrar pagos reales y venta de entradas en una versi\xF3n futura.","Media"],
            ["RNF-10","Integridad de datos","La base de datos debe utilizar claves for\xE1neas y restricciones relacionales.","Alta"],
            ["RNF-11","Carga de archivos","Las im\xE1genes deben limitarse a formatos seguros y tama\xF1o m\xE1ximo definido.","Alta"],
            ["RNF-12","Identidad visual","La interfaz debe usar una identidad inspirada en Golden Hour: dorado, negro, crema y degradados c\xE1lidos.","Media"],
          ].map((r, i) => new TableRow({ children: [
            simpleCell(r[0], { width: 1080, center: true, bg: i % 2 === 1 ? "FFFBF0" : undefined, bold: true }),
            simpleCell(r[1], { width: 1800, bg: i % 2 === 1 ? "FFFBF0" : undefined, bold: true }),
            simpleCell(r[2], { width: 5480, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[3], { width: 1000, center: true, bg: i % 2 === 1 ? "FFFBF0" : undefined,
              textColor: r[3] === "Alta" ? "C0392B" : "E67E22" })
          ]}))
        ]
      }),

      spacer(),
      heading2("3.3 Reglas de Negocio"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [1200, 8160],
        rows: [
          headerRow(["C\xF3digo", "Regla"], [1200, 8160]),
          ...[
            ["RN-01","La versi\xF3n 1 del sistema no vender\xE1 entradas ni tickets; solo gestionar\xE1 servicios y cotizaciones."],
            ["RN-02","Un visitante puede explorar servicios y armar una cotizaci\xF3n sin iniciar sesi\xF3n."],
            ["RN-03","Una cotizaci\xF3n enviada sin login debe almacenar nombre, tel\xE9fono y correo opcional del cliente."],
            ["RN-04","Si el cliente est\xE1 autenticado, la cotizaci\xF3n debe asociarse a su usuario."],
            ["RN-05","El total estimado debe calcularse en el servidor, nunca confiar \xFAnicamente en valores del formulario."],
            ["RN-06","Un proveedor solo puede gestionar los servicios asociados a su cuenta."],
            ["RN-07","El administrador puede revisar todas las cotizaciones y cambiar su estado."],
            ["RN-08","Los servicios inactivos no deben mostrarse en el cat\xE1logo p\xFAblico."],
          ].map((r, i) => new TableRow({ children: [
            simpleCell(r[0], { width: 1200, bold: true, center: true, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[1], { width: 8160, bg: i % 2 === 1 ? "FFFBF0" : undefined })
          ]}))
        ]
      }),
      pageBreak(),

      // ============ SECTION 3.4 - USE CASES ============
      heading1("3.4 Especificaci\xF3n de Casos de Uso"),
      para("Los siguientes casos de uso detallan los procesos principales del sistema Golden Hour Events. Se conserva la estructura de descripci\xF3n, actores, precondici\xF3n, flujo principal, post-condici\xF3n y flujos alternativos, adaptada al sistema de planificaci\xF3n y cotizaci\xF3n de eventos personalizados."),

      // CU 3.4.1
      ...cuSection("3.4.1 Validar Usuario", [
        ["Caso de uso", "Validar usuario"],
        ["Descripci\xF3n", "Permite autenticar a un usuario registrado para acceder a las funciones seg\xFAn su rol."],
        ["Actores", "Cliente registrado, Proveedor, Administrador"],
        ["Precondici\xF3n", "El usuario debe estar previamente registrado y activo en el sistema."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El caso de uso se inicia cuando el usuario selecciona la opci\xF3n Iniciar sesi\xF3n.",
            "2. El sistema muestra el formulario con correo electr\xF3nico y contrase\xF1a.",
            "3. El usuario ingresa sus credenciales y selecciona Ingresar.",
            "4. El sistema valida el correo, estado del usuario y contrase\xF1a.",
            "5. El sistema crea la sesi\xF3n y redirige al panel correspondiente seg\xFAn el rol.",
            "6. El caso de uso finaliza cuando el usuario accede correctamente al sistema.",
          ])
        ]],
        ["Post-condici\xF3n", "El usuario queda autenticado y con sesi\xF3n activa."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Credenciales incorrectas:",
            "  1. El sistema detecta que el correo o la contrase\xF1a no coinciden.",
            "  2. El sistema muestra el mensaje 'Credenciales incorrectas o usuario inactivo'.",
            "  3. El usuario puede volver a intentarlo.",
            "",
            "Usuario bloqueado:",
            "  1. El sistema detecta que el estado del usuario no es activo.",
            "  2. El sistema deniega el acceso y muestra un mensaje informativo.",
          ])
        ]],
      ]),

      ...cuSection("3.4.2 Registrar Usuario", [
        ["Caso de uso", "Registrar usuario"],
        ["Descripci\xF3n", "Permite crear una cuenta como cliente o proveedor para acceder a funciones personalizadas."],
        ["Actores", "Cliente, Proveedor"],
        ["Precondici\xF3n", "El usuario debe acceder al formulario de registro."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El usuario selecciona la opci\xF3n Registro.",
            "2. El sistema muestra el formulario con nombre, correo, tel\xE9fono, contrase\xF1a y rol.",
            "3. El usuario completa los datos requeridos.",
            "4. El sistema valida formato de correo, contrase\xF1a m\xEDnima y rol permitido.",
            "5. El sistema verifica que el correo no exista previamente.",
            "6. El sistema registra el usuario con contrase\xF1a cifrada.",
            "7. El sistema muestra un mensaje de registro satisfactorio.",
          ])
        ]],
        ["Post-condici\xF3n", "Se registra un nuevo usuario activo."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Correo duplicado:",
            "  1. El sistema detecta que el correo ya existe.",
            "  2. El sistema muestra un mensaje solicitando usar otro correo.",
            "",
            "Datos obligatorios incompletos:",
            "  1. El sistema detecta campos faltantes.",
            "  2. El sistema muestra el mensaje 'Faltan datos obligatorios'.",
          ])
        ]],
      ]),

      ...cuSection("3.4.3 Ver Cat\xE1logo de Servicios", [
        ["Caso de uso", "Consultar cat\xE1logo de servicios"],
        ["Descripci\xF3n", "Permite visualizar servicios disponibles sin necesidad de iniciar sesi\xF3n."],
        ["Actores", "Cliente / Visitante"],
        ["Precondici\xF3n", "El sistema debe tener servicios activos registrados."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El caso inicia cuando el usuario selecciona Servicios.",
            "2. El sistema consulta los servicios activos y disponibles.",
            "3. El sistema muestra las tarjetas de servicios con imagen, categor\xEDa, precio, ubicaci\xF3n y proveedor.",
            "4. El usuario revisa la informaci\xF3n o aplica filtros.",
            "5. El caso finaliza cuando el usuario selecciona un servicio o abandona la vista.",
          ])
        ]],
        ["Post-condici\xF3n", "El usuario visualiza el cat\xE1logo de servicios."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Sin servicios disponibles:",
            "  1. El sistema no encuentra servicios activos.",
            "  2. El sistema muestra un mensaje de cat\xE1logo vac\xEDo.",
          ])
        ]],
      ]),

      ...cuSection("3.4.4 Filtrar Servicios por Categor\xEDa", [
        ["Caso de uso", "Filtrar servicios"],
        ["Descripci\xF3n", "Permite reducir el cat\xE1logo seg\xFAn tipo de servicio: local, decoraci\xF3n, DJ, torta, catering, etc."],
        ["Actores", "Cliente / Visitante"],
        ["Precondici\xF3n", "El usuario debe encontrarse en la vista de servicios."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El usuario selecciona una categor\xEDa.",
            "2. El sistema recibe el filtro seleccionado.",
            "3. El sistema consulta los servicios activos de dicha categor\xEDa.",
            "4. El sistema muestra \xFAnicamente los resultados coincidentes.",
            "5. El usuario puede cambiar de categor\xEDa o ver el detalle de un servicio.",
          ])
        ]],
        ["Post-condici\xF3n", "Se muestran servicios filtrados por categor\xEDa."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Categor\xEDa sin resultados:",
            "  1. El sistema no encuentra servicios en la categor\xEDa seleccionada.",
            "  2. Se muestra mensaje de resultados no encontrados.",
          ])
        ]],
      ]),

      ...cuSection("3.4.5 Ver Detalle de Servicio", [
        ["Caso de uso", "Ver detalle de servicio"],
        ["Descripci\xF3n", "Permite consultar la informaci\xF3n completa de un servicio antes de agregarlo a una cotizaci\xF3n."],
        ["Actores", "Cliente / Visitante"],
        ["Precondici\xF3n", "El servicio debe existir y estar activo."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El usuario selecciona Ver detalle en una tarjeta de servicio.",
            "2. El sistema recibe el identificador del servicio.",
            "3. El sistema consulta la informaci\xF3n completa del servicio.",
            "4. El sistema muestra imagen, nombre, categor\xEDa, descripci\xF3n, precio, capacidad, ubicaci\xF3n y proveedor.",
            "5. El usuario puede volver al cat\xE1logo o agregar el servicio a su cotizaci\xF3n.",
          ])
        ]],
        ["Post-condici\xF3n", "Se muestra el detalle completo del servicio."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Servicio no encontrado:",
            "  1. El sistema no encuentra el servicio solicitado.",
            "  2. Se muestra mensaje de error y opci\xF3n de volver al cat\xE1logo.",
          ])
        ]],
      ]),

      ...cuSection("3.4.6 Armar Evento Personalizado", [
        ["Caso de uso", "Armar evento personalizado"],
        ["Descripci\xF3n", "Permite seleccionar servicios para construir una propuesta de evento personalizada."],
        ["Actores", "Cliente / Visitante"],
        ["Precondici\xF3n", "El usuario debe haber accedido a la vista Armar Evento o agregar servicios desde el cat\xE1logo."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El usuario ingresa a Armar Evento.",
            "2. El sistema muestra el formulario de datos del cliente y evento.",
            "3. El usuario agrega servicios desde el cat\xE1logo o detalle de servicio.",
            "4. El sistema mantiene temporalmente los servicios seleccionados.",
            "5. El sistema muestra subtotal por servicio y total estimado.",
            "6. El usuario completa nombre, tel\xE9fono, tipo de evento, invitados y mensaje adicional.",
          ])
        ]],
        ["Post-condici\xF3n", "El evento personalizado queda preparado para solicitar cotizaci\xF3n."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Sin servicios seleccionados:",
            "  1. El sistema detecta que no se seleccion\xF3 ning\xFAn servicio.",
            "  2. El sistema solicita agregar al menos un servicio.",
            "",
            "Cantidad inv\xE1lida:",
            "  1. El sistema detecta cantidad de invitados menor o igual a cero.",
            "  2. El sistema solicita corregir el dato.",
          ])
        ]],
      ]),

      ...cuSection("3.4.7 Solicitar Cotizaci\xF3n", [
        ["Caso de uso", "Solicitar cotizaci\xF3n"],
        ["Descripci\xF3n", "Permite registrar la solicitud de cotizaci\xF3n con los servicios seleccionados y datos del cliente."],
        ["Actores", "Cliente / Visitante"],
        ["Precondici\xF3n", "El usuario debe haber seleccionado al menos un servicio y completado los datos requeridos."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El usuario selecciona Enviar solicitud de cotizaci\xF3n.",
            "2. El sistema valida datos de contacto, evento y servicios seleccionados.",
            "3. El sistema consulta los precios reales de los servicios en base de datos.",
            "4. El sistema calcula el total estimado en el servidor.",
            "5. El sistema registra la cotizaci\xF3n y sus detalles.",
            "6. El sistema muestra pantalla de confirmaci\xF3n con resumen.",
          ])
        ]],
        ["Post-condici\xF3n", "La cotizaci\xF3n queda registrada en estado pendiente."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Datos incompletos:",
            "  1. El sistema detecta datos faltantes.",
            "  2. Se muestra un mensaje de validaci\xF3n.",
            "",
            "Error de base de datos:",
            "  1. El sistema no puede registrar la cotizaci\xF3n.",
            "  2. Se muestra mensaje de error controlado.",
          ])
        ]],
      ]),

      ...cuSection("3.4.8 Consultar Mis Cotizaciones", [
        ["Caso de uso", "Consultar cotizaciones del cliente"],
        ["Descripci\xF3n", "Permite que un cliente registrado visualice sus cotizaciones asociadas."],
        ["Actores", "Cliente registrado"],
        ["Precondici\xF3n", "El usuario debe haber iniciado sesi\xF3n con rol cliente."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El usuario selecciona Mis cotizaciones.",
            "2. El sistema consulta cotizaciones asociadas al usuario.",
            "3. El sistema muestra listado con n\xFAmero, tipo de evento, fecha, total y estado.",
            "4. El usuario revisa el estado de sus solicitudes.",
            "5. El caso finaliza cuando el usuario sale de la vista.",
          ])
        ]],
        ["Post-condici\xF3n", "El cliente visualiza sus cotizaciones registradas."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Sin cotizaciones:",
            "  1. El sistema no encuentra cotizaciones del cliente.",
            "  2. Se muestra mensaje indicando que no tiene cotizaciones registradas.",
          ])
        ]],
      ]),

      ...cuSection("3.4.9 Mantener Servicio", [
        ["Caso de uso", "Mantener servicio del proveedor"],
        ["Descripci\xF3n", "Permite al proveedor registrar y consultar servicios ofrecidos para eventos."],
        ["Actores", "Proveedor"],
        ["Precondici\xF3n", "El proveedor debe haber iniciado sesi\xF3n."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El proveedor selecciona Crear servicio.",
            "2. El sistema muestra formulario con categor\xEDa, nombre, descripci\xF3n, precio, capacidad, ubicaci\xF3n e imagen.",
            "3. El proveedor completa los datos y selecciona Guardar.",
            "4. El sistema valida campos y archivo de imagen.",
            "5. El sistema registra el servicio en la base de datos.",
            "6. El servicio queda disponible en el cat\xE1logo p\xFAblico si su estado es activo.",
          ])
        ]],
        ["Post-condici\xF3n", "El servicio queda registrado correctamente."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Archivo inv\xE1lido:",
            "  1. El sistema detecta extensi\xF3n o tama\xF1o no permitido.",
            "  2. El sistema rechaza el archivo y muestra un mensaje.",
            "",
            "Datos incompletos:",
            "  1. El sistema detecta campos obligatorios vac\xEDos.",
            "  2. Se solicita completar la informaci\xF3n.",
          ])
        ]],
      ]),

      ...cuSection("3.4.10 Gestionar Cotizaciones", [
        ["Caso de uso", "Gestionar cotizaciones"],
        ["Descripci\xF3n", "Permite al administrador revisar y actualizar el estado de las solicitudes recibidas."],
        ["Actores", "Administrador"],
        ["Precondici\xF3n", "El administrador debe haber iniciado sesi\xF3n."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El administrador selecciona Cotizaciones.",
            "2. El sistema muestra todas las cotizaciones registradas.",
            "3. El administrador revisa datos del cliente, evento, total estimado y estado.",
            "4. El administrador selecciona un nuevo estado: pendiente, contactado, aprobado, rechazado o cancelado.",
            "5. El sistema actualiza el estado.",
            "6. El caso finaliza cuando se confirma la actualizaci\xF3n.",
          ])
        ]],
        ["Post-condici\xF3n", "La cotizaci\xF3n queda actualizada con el nuevo estado."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Cambio no permitido:",
            "  1. El sistema detecta un estado inv\xE1lido.",
            "  2. Se cancela la actualizaci\xF3n y se informa al administrador.",
          ])
        ]],
      ]),

      ...cuSection("3.4.11 Gestionar Servicios", [
        ["Caso de uso", "Gestionar servicios registrados"],
        ["Descripci\xF3n", "Permite al administrador supervisar los servicios publicados por proveedores."],
        ["Actores", "Administrador"],
        ["Precondici\xF3n", "El administrador debe haber iniciado sesi\xF3n."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El administrador selecciona Servicios.",
            "2. El sistema consulta todos los servicios registrados.",
            "3. El sistema muestra proveedor, categor\xEDa, nombre, precio, capacidad, ubicaci\xF3n y estado.",
            "4. El administrador revisa la informaci\xF3n disponible.",
            "5. El caso finaliza cuando abandona la vista.",
          ])
        ]],
        ["Post-condici\xF3n", "El administrador visualiza los servicios del sistema."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Sin servicios:",
            "  1. El sistema no encuentra servicios registrados.",
            "  2. Muestra un mensaje informativo.",
          ])
        ]],
      ]),

      ...cuSection("3.4.12 Generar Reportes", [
        ["Caso de uso", "Generar reportes generales"],
        ["Descripci\xF3n", "Permite visualizar indicadores b\xE1sicos de usuarios, servicios y cotizaciones."],
        ["Actores", "Administrador"],
        ["Precondici\xF3n", "El administrador debe haber iniciado sesi\xF3n."],
        ["Flujo Principal", [
          ...multiPara([
            "1. El administrador ingresa al panel principal.",
            "2. El sistema calcula totales de usuarios, servicios, cotizaciones y cotizaciones pendientes.",
            "3. El sistema muestra las m\xE9tricas en tarjetas o tablas.",
            "4. El administrador analiza la informaci\xF3n.",
            "5. El caso finaliza cuando sale del panel.",
          ])
        ]],
        ["Post-condici\xF3n", "Se muestran reportes generales del sistema."],
        ["Flujos Alternativos", [
          ...multiPara([
            "Error de consulta:",
            "  1. El sistema no puede obtener alg\xFAn indicador.",
            "  2. Se muestra un mensaje controlado sin detener toda la pantalla.",
          ])
        ]],
      ]),

      pageBreak(),

      // ============ INTERFACES ============
      heading1("3.5 Interfaces del Sistema"),
      heading2("3.5.1 Interfaces de Usuario"),
      para("La interfaz de usuario ser\xE1 implementada mediante p\xE1ginas web con PHP, HTML, CSS y JavaScript. El sistema debe presentar navegaci\xF3n clara, formularios legibles, tarjetas de servicios con im\xE1genes, filtros por categor\xEDa, vista de detalle, cotizador y paneles por rol. El dise\xF1o visual debe estar alineado con la identidad Golden Hour mediante colores dorados, fondos c\xE1lidos, degradados, tarjetas tipo glass y dise\xF1o responsive."),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2200, 5360, 1800],
        rows: [
          headerRow(["Pantalla", "Descripci\xF3n", "Actor"], [2200, 5360, 1800]),
          ...[
            ["Inicio","Portada con descripci\xF3n del sistema, llamada a ver servicios y armar evento.","Todos"],
            ["Servicios","Cat\xE1logo p\xFAblico con filtros por categor\xEDa y tarjetas de servicios.","Cliente / Visitante"],
            ["Detalle de servicio","Vista completa del servicio con imagen, precio, ubicaci\xF3n, capacidad y proveedor.","Cliente / Visitante"],
            ["Armar evento","Formulario para seleccionar servicios, ingresar datos y calcular total estimado.","Cliente / Visitante"],
            ["Resultado de cotizaci\xF3n","Resumen de cotizaci\xF3n registrada y servicios seleccionados.","Cliente / Visitante"],
            ["Panel proveedor","Gesti\xF3n de servicios publicados por el proveedor.","Proveedor"],
            ["Panel administrador","Supervisi\xF3n de usuarios, servicios, cotizaciones y reportes.","Administrador"],
            ["Login / Registro","Autenticaci\xF3n y creaci\xF3n de usuarios.","Todos"],
          ].map((r, i) => new TableRow({ children: [
            simpleCell(r[0], { width: 2200, bold: true, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[1], { width: 5360, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[2], { width: 1800, bg: i % 2 === 1 ? "FFFBF0" : undefined })
          ]}))
        ]
      }),

      spacer(),
      heading2("3.5.2 Interfaces Hardware"),
      para("Durante el desarrollo local, el sistema requiere una computadora con Windows 10 o superior, procesador Intel Core i3 o equivalente, m\xEDnimo 4 GB de RAM y espacio libre en disco para XAMPP, base de datos, archivos del proyecto e im\xE1genes cargadas. Para producci\xF3n, se recomienda un servidor web con PHP, MySQL y capacidad suficiente para almacenamiento de im\xE1genes."),

      spacer(),
      heading2("3.5.3 Interfaces Software"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2800, 6560],
        rows: [
          headerRow(["Componente", "Descripci\xF3n"], [2800, 6560]),
          ...[
            ["Sistema operativo","Windows 10/11 durante desarrollo local; Linux recomendado para despliegue en servidor."],
            ["Servidor local","XAMPP con Apache y MySQL."],
            ["Lenguaje backend","PHP puro con PDO para acceso a datos."],
            ["Base de datos","MySQL con tablas relacionales y claves for\xE1neas."],
            ["Frontend","HTML5, CSS3 y JavaScript vanilla."],
            ["Editor","Visual Studio Code con agente Codex para asistencia de desarrollo."],
            ["Navegadores","Google Chrome, Microsoft Edge y Mozilla Firefox en versiones modernas."],
          ].map((r, i) => new TableRow({ children: [
            simpleCell(r[0], { width: 2800, bold: true, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[1], { width: 6560, bg: i % 2 === 1 ? "FFFBF0" : undefined })
          ]}))
        ]
      }),

      pageBreak(),

      // ============ DEVELOPMENT & TECH ============
      heading1("3.6 Requisitos de Desarrollo"),
      para("El ciclo de vida del desarrollo ser\xE1 incremental e iterativo. El proyecto se desarrollar\xE1 por partes para reducir errores y mantener control t\xE9cnico: estructura base, base de datos, l\xF3gica funcional, marketplace/cotizador y dise\xF1o visual responsive."),
      spacer(),
      bullet("Parte 1: Creaci\xF3n de estructura de carpetas, archivos base, plantillas e includes."),
      bullet("Parte 2: Creaci\xF3n de base de datos, tablas relacionales y conexi\xF3n PDO."),
      bullet("Parte 3: Implementaci\xF3n de autenticaci\xF3n, roles, proveedores, servicios, cotizaciones y administraci\xF3n."),
      bullet("Parte 4: Mejora visual con CSS, im\xE1genes de fondo, transiciones, responsive y experiencia premium."),
      bullet("Las pruebas se realizar\xE1n por m\xF3dulo antes de integrar cambios mayores."),
      bullet("El c\xF3digo debe mantenerse modular y legible para futuras ampliaciones."),

      spacer(),
      heading1("3.7 Requisitos Tecnol\xF3gicos"),
      spacer(),
      new Table({
        width: { size: 9360, type: WidthType.DXA },
        columnWidths: [2200, 3280, 3880],
        rows: [
          headerRow(["Tecnolog\xEDa", "Uso en el proyecto", "Justificaci\xF3n"], [2200, 3280, 3880]),
          ...[
            ["PHP puro","Backend y l\xF3gica de aplicaci\xF3n.","Permite control directo del sistema sin depender de frameworks."],
            ["MySQL","Gesti\xF3n de datos relacionales.","Adecuado para usuarios, servicios, cotizaciones y relaciones entre entidades."],
            ["PDO","Conexi\xF3n segura a base de datos.","Permite consultas preparadas y reduce riesgo de SQL Injection."],
            ["HTML5","Estructura de p\xE1ginas.","Est\xE1ndar web para interfaces accesibles."],
            ["CSS3","Dise\xF1o visual responsive.","Permite paleta Golden Hour, degradados, animaciones y tarjetas modernas."],
            ["JavaScript Vanilla","Interactividad b\xE1sica y transiciones.","Evita dependencias innecesarias y mantiene compatibilidad."],
            ["XAMPP","Servidor local de desarrollo.","Integra Apache, PHP y MySQL de forma simple en Windows."],
            ["phpMyAdmin","Gesti\xF3n de base de datos.","Facilita ejecuci\xF3n de scripts SQL y revisi\xF3n de tablas."],
            ["Visual Studio Code","Entorno de desarrollo.","Editor flexible para PHP, CSS, JS y SQL."],
          ].map((r, i) => new TableRow({ children: [
            simpleCell(r[0], { width: 2200, bold: true, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[1], { width: 3280, bg: i % 2 === 1 ? "FFFBF0" : undefined }),
            simpleCell(r[2], { width: 3880, bg: i % 2 === 1 ? "FFFBF0" : undefined })
          ]}))
        ]
      }),

      pageBreak(),

      // ============ CONCLUSION ============
      heading1("Conclusi\xF3n T\xE9cnica"),
      para("Golden Hour Events se define como una soluci\xF3n web orientada a la planificaci\xF3n de eventos personalizados. Su valor principal consiste en permitir que el cliente centralice la b\xFAsqueda de servicios, compare opciones y genere una cotizaci\xF3n estimada sin tener que consultar por separado a cada proveedor. La versi\xF3n 1 queda correctamente acotada: no vende entradas ni procesa pagos reales, sino que resuelve la etapa de selecci\xF3n y solicitud de cotizaci\xF3n."),
      spacer(),
      para("La especificaci\xF3n presentada permite continuar el desarrollo del sistema con una base clara de requisitos, actores, flujos y restricciones. Adem\xE1s, deja abierta la posibilidad de incorporar en futuras versiones pagos en l\xEDnea, confirmaci\xF3n de reservas, contratos digitales y venta de entradas, sin afectar el enfoque actual."),
      spacer(),
      spacer(),
      new Paragraph({
        border: { top: { style: BorderStyle.SINGLE, size: 6, color: GOLD } },
        spacing: { before: 240, after: 60 },
        alignment: AlignmentType.CENTER,
        children: [new TextRun({ text: "Golden Hour Events \u2014 Versi\xF3n 4.0  |  Universidad Nacional del Altiplano - FINESI  |  2026", font: "Arial", size: 18, italic: true, color: "888888" })]
      }),
    ]
  }]
});

Packer.toBuffer(doc).then(buffer => {
  fs.writeFileSync("/mnt/user-data/outputs/ERS_GoldenHourEvents_v4.docx", buffer);
  console.log("Done!");
}).catch(e => { console.error(e); process.exit(1); });
