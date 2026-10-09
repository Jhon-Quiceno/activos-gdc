<?php

namespace App\Livewire\Equipos\Concerns;

/**
 * Características propias del tipo de equipo (RF-03) para las familias que no
 * son de cómputo, guardadas en la columna JSON `equipos.caracteristicas`.
 *
 * La usan el registro (Crear) y la edición (Editar), junto con la vista
 * parcial livewire/equipos/partials/caracteristicas.blade.php. El componente
 * que la use debe tener la propiedad `$familiaSeleccionada`.
 */
trait CaracteristicasPorFamilia
{
    // --- Monitor (familia "video") ---
    public string $tamanoPulgadas = '';

    public string $conexionMonitor = '';

    // --- Impresión ---
    public string $funcionesImpresora = '';

    public string $tipoImpresion = '';

    public string $conexionImpresora = '';

    // --- Digitalización ---
    public string $tipoEscaner = '';

    public string $conexionEscaner = '';

    // --- Energía ---
    public string $tipoEnergia = '';

    public string $capacidadVa = '';

    public string $numTomas = '';

    // --- Conectividad ---
    public string $numPuertos = '';

    public string $administrable = '';

    public string $velocidad = '';

    // --- Proyección ---
    public string $lumenes = '';

    public string $resolucion = '';

    /**
     * Por familia: clave en el JSON => propiedad Livewire. Las claves son las
     * mismas que muestran la hoja de vida y el PDF.
     *
     * @var array<string, array<string, string>>
     */
    protected const CAMPOS_CARACTERISTICAS = [
        'video' => ['tamano_pulgadas' => 'tamanoPulgadas', 'conexion' => 'conexionMonitor'],
        'impresion' => ['funciones' => 'funcionesImpresora', 'tipo_impresion' => 'tipoImpresion', 'conexion' => 'conexionImpresora'],
        'digitalizacion' => ['tipo_escaner' => 'tipoEscaner', 'conexion' => 'conexionEscaner'],
        'energia' => ['tipo' => 'tipoEnergia', 'capacidad_va' => 'capacidadVa', 'numero_tomas' => 'numTomas'],
        'conectividad' => ['numero_puertos' => 'numPuertos', 'administrable' => 'administrable', 'velocidad' => 'velocidad'],
        'proyeccion' => ['lumenes' => 'lumenes', 'resolucion' => 'resolucion'],
    ];

    /** Claves que se guardan como número entero. */
    protected const CARACTERISTICAS_ENTERAS = ['numero_tomas', 'numero_puertos'];

    /** Etiquetas visibles de cada clave (las mismas de la hoja de vida y el PDF). */
    public const ETIQUETAS_CARACTERISTICAS = [
        'tamano_pulgadas' => 'Tamaño en pulgadas',
        'conexion' => 'Conexión',
        'funciones' => 'Funciones',
        'tipo_impresion' => 'Tipo de impresión',
        'tipo_escaner' => 'Tipo de escáner',
        'tipo' => 'Tipo',
        'capacidad_va' => 'Capacidad en VA',
        'numero_tomas' => 'N.° de tomas',
        'numero_puertos' => 'N.° de puertos',
        'administrable' => '¿Administrable?',
        'velocidad' => 'Velocidad',
        'lumenes' => 'Lúmenes',
        'resolucion' => 'Resolución',
    ];

    /**
     * Texto legible de unas características, para el historial
     * («Tamaño en pulgadas: 24 · Conexión: HDMI»). Ordena por clave para que
     * dos arreglos iguales den siempre el mismo texto.
     *
     * @param  array<string, mixed>|null  $caracteristicas
     */
    public static function textoCaracteristicas(?array $caracteristicas): ?string
    {
        if (! $caracteristicas) {
            return null;
        }

        ksort($caracteristicas);

        return collect($caracteristicas)
            ->map(fn ($valor, $clave) => __(self::ETIQUETAS_CARACTERISTICAS[$clave] ?? $clave).': '.$valor)
            ->implode(' · ');
    }

    /**
     * Características de la familia elegida que tienen valor, listas para la
     * columna JSON. Devuelve null si no hay ninguna (y para cómputo, que va en
     * configuración y componentes).
     *
     * @return array<string, string|int>|null
     */
    protected function caracteristicas(): ?array
    {
        $valores = [];

        foreach (self::CAMPOS_CARACTERISTICAS[$this->familiaSeleccionada] ?? [] as $clave => $propiedad) {
            $valor = trim($this->{$propiedad});

            if ($valor !== '') {
                $valores[$clave] = in_array($clave, self::CARACTERISTICAS_ENTERAS, true) ? (int) $valor : $valor;
            }
        }

        return $valores === [] ? null : $valores;
    }

    /**
     * Carga en el formulario las características guardadas de un equipo.
     *
     * @param  array<string, mixed>|null  $guardadas
     */
    protected function llenarCaracteristicas(?array $guardadas): void
    {
        foreach (self::CAMPOS_CARACTERISTICAS[$this->familiaSeleccionada] ?? [] as $clave => $propiedad) {
            $this->{$propiedad} = isset($guardadas[$clave]) ? (string) $guardadas[$clave] : '';
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function reglasCaracteristicas(): array
    {
        return [
            'numTomas' => ['nullable', 'integer', 'min:0', 'max:100'],
            'numPuertos' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
