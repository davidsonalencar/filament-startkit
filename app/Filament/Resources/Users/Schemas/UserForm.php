<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('filament/admin/user_resource.name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('filament/admin/user_resource.email'))
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->label(__('filament/admin/user_resource.password'))
                    ->password()
                    ->revealable()
                    ->required(),
                Select::make('roles')
                    ->label(__('filament/admin/user_resource.roles'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }
}
