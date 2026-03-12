<?php

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Actions\BulkExport\ExportBulkAction;
use App\Filament\Actions\Export\ExportAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make(),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament/admin/user_resource.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('filament/admin/user_resource.email'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('filament/admin/user_resource.role'))
                    ->getStateUsing(fn($record) => str($record->getRoleNames()->first() ?? 'user'))
                    ->badge()
                    ->color(fn($state): string => match ($state) {
                        'super_admin' => 'success',
                        default => 'info',
                    })
                    ->formatStateUsing(fn($state): string => str($state)->replace('_', ' ')->title())
                    ->toggleable(),
//                TextColumn::make('email_verified_at')
//                    ->label(__('filament/admin/user_resource.email_verified_at'))
//                    ->dateTime()
//                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('filament/admin/user_resource.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('filament/admin/user_resource.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
