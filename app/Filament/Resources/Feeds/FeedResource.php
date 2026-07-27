<?php

namespace App\Filament\Resources\Feeds;

use App\Filament\Resources\Feeds\Pages\EditFeed;
use App\Filament\Resources\Feeds\Pages\ListFeeds;
use App\Filament\Resources\Feeds\Schemas\FeedForm;
use App\Filament\Resources\Feeds\Tables\FeedsTable;
use App\Models\Feed;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeedResource extends Resource
{
    protected static ?string $model = Feed::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRss;

    public static function getNavigationLabel(): string
    {
        return __('sintoniza.admin.feeds');
    }

    public static function form(Schema $schema): Schema
    {
        return FeedForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeeds::route('/'),
            'edit' => EditFeed::route('/{record}/edit'),
        ];
    }
}
