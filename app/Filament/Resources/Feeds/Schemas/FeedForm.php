<?php

namespace App\Filament\Resources\Feeds\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeedForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('feed_url')
                    ->label('Feed URL')
                    ->disabled(),
                TextInput::make('title'),
                TextInput::make('url')
                    ->label('Website')
                    ->maxLength(512),
                TextInput::make('image_url')
                    ->label('Image URL'),
                TextInput::make('etag')
                    ->label('ETag'),
                TextInput::make('last_modified')
                    ->label('Last-Modified')
                    ->maxLength(64),
                DateTimePicker::make('next_fetch_at')
                    ->seconds(false),
            ]);
    }
}
