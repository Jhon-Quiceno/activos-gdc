{{--
    Estilos de los formatos para firma (RNF-13): solo negro y grises claros para
    que se lean igual impresos en blanco y negro. CSS plano (sin Tailwind)
    porque dompdf no procesa las clases utilitarias; el prefijo .fmt- evita
    choques con Tailwind en la vista previa imprimible.
--}}
<style>
    @page { size: letter portrait; margin: 1.6cm 1.5cm 1.6cm 1.5cm; }
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
