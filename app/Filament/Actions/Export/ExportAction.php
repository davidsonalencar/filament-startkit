<?php

namespace App\Filament\Actions\Export;

use App\Filament\Actions\Concerns\CanExport;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Livewire\Component;

class ExportAction
{
    use CanExport;

    public static function make(string $type = 'export'): Action
    {
        return Action::make('export')
            ->label('Exportar')
            ->icon('heroicon-o-arrow-down-tray')
            ->authorize('export')
            ->schema([
                \Filament\Forms\Components\Select::make('format')
                    ->label('Formato')
                    ->options([
                        'pdf' => 'PDF (.pdf)',
                        'xlsx' => 'Excel (.xlsx)',
                        'csv' => 'CSV (.csv)',
                    ])
                    ->default('pdf')
                    ->required(),
            ])
            ->action(function (Table $table, Component $livewire, array $data) use ($type): mixed {
                $format = $data['format'];
                $columns = self::getVisibleColumns($table);

                // Obter a query com filtros e ordenação aplicados
                $livewire = $table->getLivewire();
                $query = $livewire->getFilteredSortedTableQuery();
                // $query = $livewire->getFilteredTableQuery();
                self::applyTableSorting($table, $query);

                $records = $query->get()->map(fn($record) => self::mapRecord($record, $columns, $table));

                return self::executeExport($records, $columns, $format, $type, $livewire);
            });
    }
}
