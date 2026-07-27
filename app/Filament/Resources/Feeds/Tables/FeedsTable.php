<?php

namespace App\Filament\Resources\Feeds\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeedsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->placeholder(fn ($record) => $record->feed_url)
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('feed_url')
                    ->label('URL')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('subscriptions_count')
                    ->label(__('sintoniza.admin.subscribers'))
                    ->counts('subscriptions')
                    ->sortable(),
                TextColumn::make('episodes_count')
                    ->label(__('sintoniza.general.episodes'))
                    ->counts('episodes')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('language')
                    ->label(__('sintoniza.general.language'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('last_fetched_at')
                    ->label(__('sintoniza.admin.last_fetch'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('next_fetch_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('subscriptions_count', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
