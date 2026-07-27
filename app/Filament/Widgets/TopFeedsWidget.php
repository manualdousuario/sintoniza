<?php

namespace App\Filament\Widgets;

use App\Models\Feed;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/** Top 10 feeds by active subscriber count. */
class TopFeedsWidget extends TableWidget
{
    protected static ?string $heading = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('sintoniza.admin.top_feeds'))
            ->query(fn (): Builder => Feed::query()
                ->withCount('subscriptions')
                ->orderByDesc('subscriptions_count')
                ->limit(10))
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->placeholder(fn (Feed $record) => $record->feed_url)
                    ->limit(50),
                TextColumn::make('feed_url')
                    ->label(__('sintoniza.admin.feed_url'))
                    ->limit(60),
                TextColumn::make('subscriptions_count')
                    ->label(__('sintoniza.admin.subscribers')),
            ]);
    }
}
