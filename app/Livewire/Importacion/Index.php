<?php

namespace App\Livewire\Importacion;

use App\Models\Dependencia;
use Livewire\Component;

class Index extends Component
{
    /**
     * Paso activo del wizard de importación (1 a 4). Por defecto arranca en 3
     * ("Vista previa") porque es la parte más representativa del flujo: el
     * archivo y las equivalencias ya se resolvieron en los pasos anteriores.
     */
    public int $pasoActual = 3;

    /**
     * Valor oficial elegido para cada equivalencia detectada en el archivo.
     * Se inicializa con la sugerencia automática (coincidencia más cercana
     * contra el catálogo de Dependencia) para que el usuario solo tenga que
     * corregir las que de verdad lo requieran.
     */
    public array $equivalencias = [];

    public function mount(): void
    {
        $this->equivalencias = [
            ['origen' => 'Sec Educación', 'sugerido' => 'Secretaría de Educación'],
            ['origen' => 'Sec. Infraestructura', 'sugerido' => 'Secretaría de Infraestructura'],
            ['origen' => 'Hacienda', 'sugerido' => 'Secretaría de Hacienda'],
            ['origen' => 'TICs', 'sugerido' => 'Dirección TIC'],
        ];
    }

    public function irAPaso(int $paso): void
    {
        $this->pasoActual = max(1, min(4, $paso));
    }

    public function volver(): void
    {
        $this->irAPaso($this->pasoActual - 1);
    }

    public function continuar(): void
    {
        $this->irAPaso($this->pasoActual + 1);
    }

    public function render()
    {
        $pasos = [
            1 => __('Subir archivo'),
            2 => __('Equivalencias'),
            3 => __('Vista previa'),
            4 => __('Confirmar'),
        ];

        $kpis = [
            'filas_leidas' => 461,
            'equipos_detectados' => 930,
            'con_advertencias' => 23,
            'excluidos' => 8,
        ];

        $advertencias = [
            ['fila' => 12, 'valor' => 'I1-025646', 'detalle' => __('Serial normalizado automáticamente (se eliminó un espacio).'), 'tipo' => 'corregido'],
            ['fila' => 47, 'valor' => 'SIN-SERIAL', 'detalle' => __('El valor de serial no es reconocible, requiere revisión manual.'), 'tipo' => 'revisar'],
            ['fila' => 58, 'valor' => 'I1-018200', 'detalle' => __('Este serial ya existe en la fila 203.'), 'tipo' => 'repetido'],
            ['fila' => 101, 'valor' => 'N/A', 'detalle' => __('No trae código de activo asignado.'), 'tipo' => 'sin_codigo'],
            ['fila' => 134, 'valor' => 'N/A', 'detalle' => __('No trae número de cédula del responsable.'), 'tipo' => 'sin_cedula'],
            ['fila' => 201, 'valor' => 'Portátil Dell Latitude 5420', 'detalle' => __('Marcado como equipo personal, se excluye de la importación.'), 'tipo' => 'excluido'],
            ['fila' => 203, 'valor' => 'I1-018200', 'detalle' => __('Serial repetido; se conservará el de la fila 58.'), 'tipo' => 'repetido'],
        ];

        $tiposAdvertencia = [
            'corregido' => ['variant' => 'success', 'label' => __('Corregido')],
            'revisar' => ['variant' => 'warning', 'label' => __('Revisar')],
            'repetido' => ['variant' => 'danger', 'label' => __('Repetido')],
            'sin_codigo' => ['variant' => 'warning', 'label' => __('Sin código')],
            'sin_cedula' => ['variant' => 'warning', 'label' => __('Sin cédula')],
            'excluido' => ['variant' => 'neutral', 'label' => __('Excluido')],
        ];

        $dependencias = Dependencia::orderBy('nombre')->pluck('nombre');

        return view('livewire.importacion.index', [
            'pasos' => $pasos,
            'kpis' => $kpis,
            'advertencias' => $advertencias,
            'tiposAdvertencia' => $tiposAdvertencia,
            'dependencias' => $dependencias,
        ]);
    }
}
