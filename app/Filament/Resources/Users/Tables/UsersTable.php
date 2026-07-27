<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('sintoniza.admin.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('sintoniza.general.email'))
                    ->searchable(),
                IconColumn::make('is_admin')
                    ->label(__('sintoniza.admin.admin'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('sintoniza.admin.active'))
                    ->boolean(),
                TextColumn::make('subscriptions_count')
                    ->label(__('sintoniza.admin.subscriptions'))
                    ->counts('subscriptions')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('sintoniza.admin.active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }
}
