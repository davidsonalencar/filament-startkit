<?php

use App\Models\Export;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->get('/exports/{export}', function (Export $export) {
    if (auth()->user()->cannot('view', $export)) {
        abort(403);
    }

    if (!Storage::disk('temp')->exists($export->file_path)) {
        abort(404);
    }

    if ($export->format === 'pdf') {
        return Storage::disk('temp')->response($export->file_path, $export->file_name, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $export->file_name . '"',
        ]);
    }

    return Storage::disk('temp')->download($export->file_path, $export->file_name);
})->name('exports.download');
