<?php

namespace App\Filament\Actions\Concerns;

use App\Exports\FilamentExport;
use App\Jobs\ExportJob;
use App\Models\Export;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Column;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

trait CanExport
{
    protected static function getVisibleColumns(Table $table): array
    {
        $columns = [];
        foreach ($table->getColumns() as $column) {
            if ($column->isToggleable() && $column->isToggledHidden()) {
                continue;
            }
            $label = $column->getLabel();
            if ($label) {
                $columns[$column->getName()] = $label;
            }
        }
        return $columns;
    }

    protected static function applyTableSorting(Table $table, Builder $query): void
    {
        $sortColumn = $table->getSortColumn();
        $sortDirection = $table->getSortDirection();

        if ($sortColumn && $sortDirection) {
            $query->orderBy($sortColumn, $sortDirection);
        }
    }

    protected static function mapRecord($record, array $columns, Table $table = null): array
    {
        $data = [];

        if (!$table) {
            foreach (array_keys($columns) as $columnName) {
                $data[$columnName] = data_get($record, $columnName);
            }

            return $data;
        }

        $tableColumns = collect($table->getColumns())->keyBy(fn(Column $column) => $column->getName());

        foreach (array_keys($columns) as $columnName) {
            /** @var Column|null $column */
            $column = $tableColumns->get($columnName);

            if (!$column) {
                $data[$columnName] = data_get($record, $columnName);
                continue;
            }

            // Vincula o registro atual à coluna
            $column->record($record);

            // Pega o estado bruto respeitando getStateUsing(), relationships etc.
            $state = method_exists($column, 'getFormattedState')
                ? $column->getFormattedState()
                : $column->getState();

            // Aplica a formatação da coluna: dateTime(), money(), numeric(), formatStateUsing()...
            $value = method_exists($column, 'formatState')
                ? $column->formatState($state)
                : $state;

            // Normaliza valores renderizáveis para exportação
            if ($value instanceof Htmlable) {
                $value = $value->toHtml();
            }

            if (is_string($value)) {
                $value = trim(strip_tags($value));
            }

            if (is_array($value)) {
                $value = implode(', ', array_map(
                    fn($item) => is_scalar($item) ? (string)$item : json_encode($item, JSON_UNESCAPED_UNICODE),
                    $value
                ));
            }

            $data[$columnName] = $value;
        }

        return $data;
    }

    protected static function executeExport(Collection $records, array $columns, string $format, string $type = 'export', HasTable $livewire = null)
    {
        $totalRecords = $records->count();
        $threshold = config('app.export_queue_threshold', 100);
        $fileName = $type . '-' . now()->timestamp . '.' . ($format === 'pdf' ? 'pdf' : $format);

        if ($totalRecords > $threshold) {
            ExportJob::dispatch(
                $records,
                $columns,
                $format,
                auth()->user(),
                $fileName,
                $type
            );

            Notification::make()
                ->title('Exportação em processamento')
                ->body('Como o arquivo possui muitos registros, ele será processado em segundo plano. Você será notificado quando terminar.')
                ->info()
                ->send();

            return null;
        }

        $filePath = 'exports/' . $fileName;

        if ($format === 'pdf') {
            $content = Pdf::view('pdf.reports.export', [
                'records' => $records,
                'columns' => $columns,
            ])->format('a4')->generatePdfContent();

            Storage::disk('temp')->put($filePath, $content);
        } else {
            $writerType = match ($format) {
                'csv' => \Maatwebsite\Excel\Excel::CSV,
                default => \Maatwebsite\Excel\Excel::XLSX,
            };

            Excel::store(
                new FilamentExport($records, $columns),
                $filePath,
                'temp',
                $writerType
            );
        }

        $export = Export::create([
            'user_id' => auth()->id(),
            'file_name' => $fileName,
            'file_path' => $filePath,
            'format' => $format,
            'type' => $type,
        ]);

        if ($format === 'pdf' && $livewire) {
            $livewire->dispatch('open-url', url: route('exports.download', $export));
            return null;
        }

        return redirect()->route('exports.download', $export);
    }
}
