<?php

namespace App\Livewire\Reportes\Exportes;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReporteExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private Collection $filas,
        private array $encabezados,
    ) {}

    public function collection(): Collection
    {
        return $this->filas;
    }

    public function headings(): array
    {
        return $this->encabezados;
    }
}