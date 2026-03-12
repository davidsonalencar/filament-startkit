<?php

namespace App\Filament\Resources\Tasks\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament/admin/task_resource.name'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('filament/admin/task_resource.description'))
                    ->columnSpanFull(),
                Toggle::make('is_completed')
                    ->label(__('filament/admin/task_resource.is_completed'))
                    ->required(),
            ]);
    }
}
