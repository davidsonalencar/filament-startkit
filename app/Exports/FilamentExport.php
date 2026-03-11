<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FilamentExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected Collection $records,
        protected array      $columns
    )
    {
    }

    public function collection(): Collection
    {
        return $this->records;
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function map($record): array
    {
        $row = [];
        foreach (array_keys($this->columns) as $columnName) {
            // Tenta obter o estado formatado ou o valor bruto
            // Nota: Em jobs de fila, o Filament Table Column não está disponível facilmente,
            // então passamos os dados já processados ou usamos lógica simples aqui.
            $row[] = data_get($record, $columnName);
        }
        return $row;
    }
}
