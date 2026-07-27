<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('sintoniza.admin.name'))
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabledOn('edit'),
                TextInput::make('email')
                    ->label(__('sintoniza.general.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->label(__('sintoniza.general.password'))
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('language')
                    ->label(__('sintoniza.general.language'))
                    ->options(__('sintoniza.language_names'))
                    ->default('en')
                    ->required(),
                Toggle::make('is_admin')
                    ->label(__('sintoniza.admin.admin')),
                Toggle::make('is_active')
                    ->label(__('sintoniza.admin.active'))
                    ->default(true),
            ]);
    }
}
