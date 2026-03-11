<?php

namespace App\Jobs;

use App\Exports\FilamentExport;
use App\Models\Export;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

class ExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Collection $records,
        protected array      $columns,
        protected string     $format,
        protected            $user,
        protected string     $fileName,
        protected string     $type = 'export'
    )
    {
    }

    public function handle()
    {
        $filePath = 'exports/' . $this->fileName;

        if ($this->format === 'pdf') {
            $pdf = Pdf::view('pdf.reports.export', [
                'records' => $this->records,
                'columns' => $this->columns,
                'title' => 'Export'
            ])->format('a4');

            Storage::disk('temp')->put($filePath, $pdf->generatePdfContent());
        } else {
            $writerType = match ($this->format) {
                'csv' => \Maatwebsite\Excel\Excel::CSV,
                default => \Maatwebsite\Excel\Excel::XLSX,
            };

            Excel::store(
                new FilamentExport($this->records, $this->columns),
                $filePath,
                'temp',
                $writerType
            );
        }

        $export = Export::create([
            'user_id' => $this->user->id,
            'file_name' => $this->fileName,
            'file_path' => $filePath,
            'format' => $this->format,
            'type' => $this->type,
        ]);

        $url = route('exports.download', $export);

        Notification::make()
            ->title('Exportação concluída')
            ->body('Seu arquivo ' . $this->fileName . ' está pronto.')
            ->success()
            ->actions([
                Action::make('view')
                    ->label($this->format === 'pdf' ? 'Visualizar PDF' : 'Download')
                    ->button()
                    ->url($url)
                    ->openUrlInNewTab(),
            ])
            ->sendToDatabase($this->user)
            ->broadcast($this->user)
            ->send();
    }
}
