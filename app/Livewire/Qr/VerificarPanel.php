<?php

namespace App\Livewire\Qr;

use App\Models\Equipo;
use App\Services\HistorialService;
use Livewire\Component;

/**
 * Pantalla "Verificar en sitio" (Manuel, bloque de Etiquetas QR): mockup de app
 * móvil para que un técnico confirme en terreno que un equipo físico corresponde
 * a su ficha en el sistema.
 *
 * Esta vista NO usa <x-layouts.app-shell>: tiene su propio documento HTML
 * completo (ver resources/views/livewire/qr/verificar.blade.php), igual que
 * resources/views/layouts/guest.blade.php hace para las pantallas de auth.
 */
class VerificarPanel extends Component
{
    public string $busqueda = '';

    // --- Formulario rápido ---
    public string $serialFabricante = '';

    /** RAM y disco son solo visuales en este formulario rápido: no se persisten. */
    public string $memoriaRam = '';

    public string $tipoDisco = '';

    public string $procesador = '';

    public bool $responsableCorrecto = true;

    /** Sin cámara real todavía (TODO aceptado, fuera de alcance). */
    public bool $tomarFoto = false;

    public function updatedBusqueda(): void
    {
        $equipo = $this->buscarEquipo();

        $this->serialFabricante = $equipo?->serial ?? '';
    }

    private function buscarEquipo(): ?Equipo
    {
        $termino = trim($this->busqueda);

        if ($termino === '') {
            return null;
        }

        return Equipo::with(['tipoEquipo', 'marca', 'asignacionActual.persona', 'asignacionActual.dependencia'])
            ->where('serial', 'like', "%{$termino}%")
            ->orWhere('codigo_activo', 'like', "%{$termino}%")
            ->first();
    }

    public function marcarVerificado(): void
    {
        $equipo = $this->buscarEquipo();

        if (! $equipo) {
            return;
        }

        $equipo->update(['verificacion' => 'verificado']);

        app(HistorialService::class)->registrar(
            equipo: $equipo,
            tipo: 'actualizacion_datos',
            usuario: auth()->user(),
            descripcion: 'Verificado en sitio',
        );

        session()->flash('status', __('Equipo marcado como verificado.'));
    }

    public function render()
    {
        return view('livewire.qr.verificar-panel', [
            'equipo' => $this->buscarEquipo(),
        ]);
    }
}
