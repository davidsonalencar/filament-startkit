<?php

namespace App\Filament\Actions\BulkExport;

use App\Filament\Actions\Concerns\CanExport;
use Filament\Actions\BulkAction;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class ExportBulkAction
{
    use CanExport;

    public static function make(string $type = 'bulk-export'): BulkAction
    {
        return BulkAction::make('bulkExport')
            ->label('Exportar Selecionados')
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
            ->action(function (Table $table, Collection $records, array $data) use ($type): mixed {
                $format = $data['format'];
                $columns = self::getVisibleColumns($table);

                $mappedRecords = $records->map(fn($record) => self::mapRecord($record, $columns));

                return self::executeExport($mappedRecords, $columns, $format, $type, $table->getLivewire());
            });
    }
}
