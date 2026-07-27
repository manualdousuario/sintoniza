<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('sintoniza.general.username'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->label(__('sintoniza.admin.feed_url'))
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('feed.title')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('updated_at')
                    ->label(__('sintoniza.dashboard.last_update'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('deleted_at')
                    ->label(__('sintoniza.admin.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('sintoniza.admin.inactive') : __('sintoniza.admin.active'))
                    ->color(fn ($state) => $state ? 'danger' : 'success'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
