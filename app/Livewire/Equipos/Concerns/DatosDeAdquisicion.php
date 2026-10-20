<?php

namespace App\Livewire\Equipos\Concerns;

use App\Livewire\Equipos\Soporte\TextosEquipo;
use App\Models\Equipo;
use App\Models\Proveedor;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Datos de adquisición del equipo (RF-52) y datos de red y ofimática de la
 * configuración de cómputo (docs/base-de-datos.md §4.4 y §4.5). Lo comparten
 * el registro y la edición.
 *
 * El valor se escribe en pesos colombianos (RNF-14) como se acostumbra:
 * «1.250.000» o «1250000,50»; se guarda como número.
 */
trait DatosDeAdquisicion
{
    public string $formaIngreso = 'compra';

    public string $fechaAdquisicion = '';

    public string $valorAdquisicion = '';

    public ?int $proveedorId = null;

    public string $numeroContrato = '';

    public string $numeroFactura = '';

    public string $fechaFinGarantia = '';

    /** Vacío = la vida útil del tipo de equipo. */
    public string $vidaUtilMeses = '';

    // --- Ofimática y red (solo tipos con configuración de cómputo) ---
    public string $ofimatica = '';

    public string $licenciaOfimatica = '';

    public string $dominio = '';

    public string $direccionIp = '';

    public string $direccionMac = '';

    protected function reglasAdquisicion(): array
    {
        $reglas = [
            'formaIngreso' => ['required', Rule::in(array_keys(Equipo::FORMAS_INGRESO))],
            'fechaAdquisicion' => ['nullable', 'date', 'before_or_equal:today'],
            'valorAdquisicion' => ['nullable', 'regex:/^\d{1,13}(,\d{1,2})?$/'],
            'proveedorId' => ['nullable', 'exists:proveedores,id'],
            'numeroContrato' => ['nullable', 'string', 'max:40'],
            'numeroFactura' => ['nullable', 'string', 'max:40'],
            'fechaFinGarantia' => ['nullable', 'date', ...($this->fechaAdquisicion !== '' ? ['after_or_equal:fechaAdquisicion'] : [])],
            'vidaUtilMeses' => ['nullable', 'integer', 'min:1', 'max:600'],
        ];

        if ($this->usaConfiguracionComputo) {
            $reglas += [
                'ofimatica' => ['nullable', 'string', 'max:80'],
                'licenciaOfimatica' => ['nullable', 'string', 'max:80'],
                'dominio' => ['nullable', 'string', 'max:60'],
                'direccionIp' => ['nullable', 'ip'],
                'direccionMac' => ['nullable', 'regex:/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/'],
            ];
        }

        return $reglas;
    }

    protected function mensajesAdquisicion(): array
    {
        return [
            'valorAdquisicion.regex' => __('Escribe el valor en pesos, por ejemplo 2.350.000 o 2350000,50.'),
            'fechaFinGarantia.after_or_equal' => __('La garantía no puede terminar antes de la fecha de adquisición.'),
            'direccionMac.regex' => __('La MAC debe tener 6 pares hexadecimales, por ejemplo 3C:52:82:1A:2B:4C.'),
        ];
    }

    protected function atributosAdquisicion(): array
    {
        return [
            'formaIngreso' => __('forma de ingreso'),
            'fechaAdquisicion' => __('fecha de adquisición'),
            'valorAdquisicion' => __('valor'),
            'proveedorId' => __('proveedor'),
            'numeroContrato' => __('número de contrato'),
            'numeroFactura' => __('número de factura'),
            'fechaFinGarantia' => __('fin de la garantía'),
            'vidaUtilMeses' => __('vida útil'),
            'ofimatica' => __('ofimática'),
            'licenciaOfimatica' => __('licencia de ofimática'),
            'dominio' => __('dominio'),
            'direccionIp' => __('dirección IP'),
            'direccionMac' => __('dirección MAC'),
        ];
    }

    /**
     * Quita los puntos de miles: «2.350.000,50» queda «2350000,50» (y
     * «2350000.50», escrito con punto decimal, también).
     */
    public function updatedValorAdquisicion(): void
    {
        $this->valorAdquisicion = $this->normalizarValor($this->valorAdquisicion);
    }

    /**
     * «3c-52-82-1a-2b-4c» o «3c52821a2b4c» quedan «3C:52:82:1A:2B:4C».
     */
    public function updatedDireccionMac(): void
    {
        $this->direccionMac = $this->normalizarMac($this->direccionMac);
    }

    protected function normalizarValor(string $valor): string
    {
        $valor = preg_replace('/[\s$]+/u', '', trim($valor));

        // «2350000.5»: un solo punto con 1 o 2 decimales al final es la coma decimal, no de miles.
        if (preg_match('/^\d+\.\d{1,2}$/', $valor)) {
            return str_replace('.', ',', $valor);
        }

        return str_replace('.', '', $valor);
    }

    protected function normalizarMac(string $mac): string
    {
        $hex = strtoupper(preg_replace('/[^0-9A-Fa-f]/', '', $mac));

        return strlen($hex) === 12 ? implode(':', str_split($hex, 2)) : strtoupper(trim($mac));
    }

    /** Normaliza antes de validar, por si se guarda sin haber salido del campo. */
    protected function normalizarAdquisicion(): void
    {
        $this->valorAdquisicion = $this->normalizarValor($this->valorAdquisicion);
        $this->direccionMac = $this->normalizarMac($this->direccionMac);
        $this->direccionIp = trim($this->direccionIp);
    }

    /** Columnas de `equipos` listas para guardar. */
    protected function columnasAdquisicion(): array
    {
        return [
            'forma_ingreso' => $this->formaIngreso,
            'fecha_adquisicion' => $this->fechaAdquisicion !== '' ? $this->fechaAdquisicion : null,
            'valor_adquisicion' => $this->valorAdquisicion !== '' ? str_replace(',', '.', $this->valorAdquisicion) : null,
            'proveedor_id' => $this->proveedorId,
            'numero_contrato' => $this->textoONulo($this->numeroContrato),
            'numero_factura' => $this->textoONulo($this->numeroFactura),
            'fecha_fin_garantia' => $this->fechaFinGarantia !== '' ? $this->fechaFinGarantia : null,
            'vida_util_meses' => $this->vidaUtilMeses !== '' ? (int) $this->vidaUtilMeses : null,
        ];
    }

    /** Columnas nuevas de `configuraciones_computo`. */
    protected function columnasRedYOfimatica(): array
    {
        return [
            'ofimatica' => $this->textoONulo($this->ofimatica),
            'licencia_ofimatica' => $this->textoONulo($this->licenciaOfimatica),
            'dominio' => $this->textoONulo($this->dominio),
            'direccion_ip' => $this->textoONulo($this->direccionIp),
            'direccion_mac' => $this->textoONulo($this->direccionMac),
        ];
    }

    protected function llenarAdquisicion(Equipo $equipo): void
    {
        $this->formaIngreso = $equipo->forma_ingreso ?? 'carga_inicial';
        $this->fechaAdquisicion = (string) $equipo->fecha_adquisicion?->toDateString();
        $this->valorAdquisicion = $equipo->valor_adquisicion !== null ? TextosEquipo::valorParaFormulario($equipo->valor_adquisicion) : '';
        $this->proveedorId = $equipo->proveedor_id;
        $this->numeroContrato = (string) $equipo->numero_contrato;
        $this->numeroFactura = (string) $equipo->numero_factura;
        $this->fechaFinGarantia = (string) $equipo->fecha_fin_garantia?->toDateString();
        $this->vidaUtilMeses = $equipo->vida_util_meses !== null ? (string) $equipo->vida_util_meses : '';

        if ($configuracion = $equipo->configuracionComputo) {
            $this->ofimatica = (string) $configuracion->ofimatica;
            $this->licenciaOfimatica = (string) $configuracion->licencia_ofimatica;
            $this->dominio = (string) $configuracion->dominio;
            $this->direccionIp = (string) $configuracion->direccion_ip;
            $this->direccionMac = (string) $configuracion->direccion_mac;
        }
    }

    /**
     * Valores legibles de adquisición para el antes/después del historial.
     *
     * @return array<string, string|null>
     */
    protected function textosAdquisicion(
        ?string $formaIngreso,
        ?string $fecha,
        ?string $valor,
        ?int $proveedorId,
        ?string $contrato,
        ?string $factura,
        ?string $finGarantia,
        ?string $vidaUtil,
    ): array {
        $fechaTexto = fn (?string $f) => filled($f) ? Carbon::parse($f)->format('d/m/Y') : null;

        return [
            'forma_ingreso' => $formaIngreso ? (Equipo::FORMAS_INGRESO[$formaIngreso] ?? $formaIngreso) : null,
            'fecha_adquisicion' => $fechaTexto($fecha),
            'valor_adquisicion' => TextosEquipo::pesos(filled($valor) ? str_replace(',', '.', $valor) : null),
            'proveedor' => $proveedorId ? Proveedor::query()->whereKey($proveedorId)->value('razon_social') : null,
            'numero_contrato' => $contrato,
            'numero_factura' => $factura,
            'fecha_fin_garantia' => $fechaTexto($finGarantia),
            'vida_util_meses' => filled($vidaUtil) ? __(':n meses', ['n' => $vidaUtil]) : null,
        ];
    }

    private function textoONulo(string $texto): ?string
    {
        return trim($texto) !== '' ? trim($texto) : null;
    }
}
