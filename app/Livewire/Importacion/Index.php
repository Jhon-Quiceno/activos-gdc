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
            ['origen' => 'Educación', 'sugerido' => 'Secretaría de Educación'],
            ['origen' => 'Sec infraestructura', 'sugerido' => 'Secretaría de Infraestructura'],
            ['origen' => 'Almacen', 'sugerido' => 'Almacén'],
            ['origen' => 'W10 pro', 'sugerido' => 'Windows 10 Pro'],
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
            ['fila' => 2, 'valor' => 'I 1 26004', 'detalle' => __('Código con espacios; se normalizó a I1-026004.'), 'tipo' => 'corregido'],
            ['fila' => 4, 'valor' => '-022894', 'detalle' => __('Código incompleto.'), 'tipo' => 'revisar'],
            ['fila' => 9, 'valor' => '020410', 'detalle' => __('Código sin prefijo I1.'), 'tipo' => 'revisar'],
            ['fila' => 82, 'valor' => 'I1-001664', 'detalle' => __('Código repetido entre PC y monitor.'), 'tipo' => 'repetido'],
            ['fila' => 94, 'valor' => 'Sin Código', 'detalle' => __('Impresora sin código de activo.'), 'tipo' => 'sin_codigo'],
            ['fila' => 6, 'valor' => '—', 'detalle' => __('Sin cédula: «No se encontraba en su lugar».'), 'tipo' => 'sin_cedula'],
            ['fila' => '—', 'valor' => 'No es de la gobernación', 'detalle' => __('Equipo personal: se excluye.'), 'tipo' => 'excluido'],
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
