{{--
    Estilos de los formatos para firma (RNF-13): solo negro y grises claros para
    que se lean igual impresos en blanco y negro. CSS plano (sin Tailwind)
    porque dompdf no procesa las clases utilitarias; el prefijo .fmt- evita
    choques con Tailwind en la vista previa imprimible.
--}}
<style>
    /* Tamaño oficio (21,59 × 33 cm) y márgenes del formato oficial en Word. */
    @page { size: 8.5in 13in portrait; margin: 2.5cm 3cm 2.5cm 3cm; }

    /* --- Formato oficial (partials/oficial.blade.php) --- */
    .ofi { font-family: Verdana, 'DejaVu Sans', Arial, sans-serif; font-size: 8pt; color: #000; line-height: 1.35; }
    .ofi .ofi-logo { height: 62px; margin-bottom: 10px; }
    .ofi .ofi-tabla { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .ofi .ofi-tabla td { border: 1px solid #000; padding: 5px 6px; vertical-align: middle; text-align: left; }
    /* «td.» en los selectores para ganarle a la regla general de las celdas. */
    .ofi .ofi-tabla td.ofi-titulo { font-size: 12pt; font-weight: bold; text-align: center; height: 28px; }
    .ofi .ofi-tabla td.ofi-version { text-align: center; font-size: 8pt; }
    .ofi .ofi-tabla td.ofi-gris { background: #BFBFBF; font-weight: bold; height: 18px; }
    .ofi .ofi-tabla td.ofi-centro { text-align: center; }
    .ofi .ofi-etiqueta { font-weight: bold; }
    .ofi .ofi-etiqueta-linea { font-weight: bold; }
    .ofi .ofi-tabla td.ofi-caja-diagnostico { height: 80px; vertical-align: top; text-align: justify; }
    .ofi .ofi-tabla td.ofi-caja-recomendaciones { height: 120px; vertical-align: top; text-align: justify; }
    .ofi .ofi-consecutivo { font-size: 7pt; color: #444; text-align: right; margin: 4px 0 0; }
    .ofi .ofi-firmas { width: 100%; border-collapse: collapse; margin-top: 70px; page-break-inside: avoid; }
    .ofi .ofi-firmas td { border: none; width: 50%; padding: 0 18px; vertical-align: top; font-size: 8pt; }
    .ofi .ofi-linea-firma { border-top: 1px solid #000; margin-bottom: 4px; }
    .ofi .ofi-nombre { color: #222; }

    .fmt { font-family: 'DejaVu Sans', 'Source Sans 3', Arial, sans-serif; font-size: 10px; color: #000; line-height: 1.35; }
    .fmt table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .fmt tr { page-break-inside: avoid; }
    .fmt .fmt-bloque { page-break-inside: avoid; }
    .fmt td, .fmt th { border: 1px solid #000; padding: 4px 6px; vertical-align: top; text-align: left; }
    .fmt .fmt-seccion { background: #d9d9d9; font-weight: bold; text-transform: uppercase; letter-spacing: .3px; font-size: 9.5px; }
    .fmt .fmt-label { background: #f0f0f0; font-weight: bold; width: 22%; }
    .fmt .fmt-mono { font-family: 'DejaVu Sans Mono', monospace; }
    .fmt .fmt-centro { text-align: center; }
    .fmt .fmt-titulo { font-size: 13px; font-weight: bold; text-transform: uppercase; }
    .fmt .fmt-muted { color: #444; }
    .fmt .fmt-caja { height: 52px; }
    .fmt .fmt-caja-alta { height: 74px; }
    .fmt .fmt-legal { margin: 8px 0 18px; font-size: 9.5px; text-align: justify; }
    .fmt .fmt-firmas { margin-top: 6px; page-break-inside: avoid; }
    .fmt .fmt-firmas td { border: none; padding: 0 14px; width: 50%; }
    .fmt .fmt-linea-firma { border-top: 1px solid #000; margin-top: 54px; padding-top: 4px; }
    .fmt .fmt-logo { height: 58px; }
    .fmt .fmt-equipo-titulo { font-weight: bold; font-size: 10.5px; margin: 6px 0 3px; }
</style>
