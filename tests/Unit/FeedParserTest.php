<?php

use App\Services\FeedParser;
use Tests\TestCase;

uses(TestCase::class);

function feedFixture(string $name): string
{
    return file_get_contents(base_path("tests/Fixtures/{$name}"));
}

function rssDocument(string $channelInner): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<rss version="2.0" xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"'
        .' xmlns:atom="http://www.w3.org/2005/Atom">'
        ."<channel><title>T</title>{$channelInner}</channel></rss>";
}

function rssWithDuration(string $itemExtra, string $enclosureAttrs = ''): string
{
    return rssDocument(
        "<item><title>E</title><enclosure url=\"https://cdn.example.com/e.mp3\" {$enclosureAttrs} type=\"audio/mpeg\"/>{$itemExtra}</item>"
    );
}

// ---------------------------------------------------------------- RSS 2.0

it('parses an RSS 2.0 feed with itunes fields', function () {
    $parser = new FeedParser('https://feeds.example.com/fixture.xml');

    expect($parser->parse(feedFixture('rss-itunes.xml')))->toBeTrue();

    expect($parser->title)->toBe('Fixture Podcast')
        ->and($parser->url)->toBe('https://podcast.example.com')
        ->and($parser->description)->toBe('A podcast used in tests.')
        ->and($parser->language)->toBe('en')
        ->and($parser->image_url)->toBe('https://podcast.example.com/cover.jpg')
        ->and($parser->published_at)->toBeInstanceOf(DateTimeInterface::class)
        ->and($parser->published_at->getTimestamp())->toBe(strtotime('2024-01-15 10:00:00 UTC'));

    // atom:link rel="self" matches the requested URL: no canonical rewrite.
    expect($parser->feed_url)->toBe('https://feeds.example.com/fixture.xml')
        ->and($parser->getAliases())->toBe([]);

    $episodes = $parser->listEpisodes();

    // The third item has no audio and is skipped.
    expect($episodes)->toHaveCount(2);

    $one = $episodes[0];
    expect($one->media_url)->toBe('https://cdn.example.com/ep1.mp3')
        ->and($one->title)->toBe('Episode One')
        ->and($one->url)->toBe('https://podcast.example.com/episodes/1')
        ->and($one->image_url)->toBe('https://podcast.example.com/ep1.jpg')
        ->and($one->published_at->getTimestamp())->toBe(strtotime('2024-01-01 10:00:00 UTC'))
        // enclosure length (bytes) -> seconds at 128 kbps
        ->and($one->duration)->toBe(2000);

    $two = $episodes[1];
    expect($two->media_url)->toBe('https://cdn.example.com/ep2.mp3')
        // plain <description> wins over content:encoded
        ->and($two->description)->toBe('Short description.')
        // itunes:duration H:MM:SS
        ->and($two->duration)->toBe(3723);
});

// --------------------------------------------------------------------- Atom

it('parses an Atom feed', function () {
    $parser = new FeedParser('https://atom.example.com/feed.xml');

    expect($parser->parse(feedFixture('atom.xml')))->toBeTrue();

    expect($parser->title)->toBe('Atom Fixture')
        ->and($parser->url)->toBe('https://atom.example.com')
        ->and($parser->description)->toBe('Atom feed used in tests.')
        ->and($parser->image_url)->toBe('https://atom.example.com/logo.png')
        ->and($parser->published_at->getTimestamp())->toBe(strtotime('2024-01-15 10:00:00 UTC'));

    expect($parser->feed_url)->toBe('https://atom.example.com/feed.xml')
        ->and($parser->getAliases())->toBe([]);

    $episodes = $parser->listEpisodes();
    expect($episodes)->toHaveCount(2);

    $one = $episodes[0];
    expect($one->media_url)->toBe('https://cdn.example.com/atom1.mp3')
        ->and($one->title)->toBe('Atom Episode One')
        ->and($one->url)->toBe('https://atom.example.com/episodes/1')
        ->and($one->description)->toBe('<p>Atom episode one.</p>')
        ->and($one->image_url)->toBe('https://atom.example.com/thumb1.jpg')
        ->and($one->published_at->getTimestamp())->toBe(strtotime('2024-01-01 10:00:00 UTC'))
        ->and($one->duration)->toBe(123);

    $two = $episodes[1];
    expect($two->media_url)->toBe('https://cdn.example.com/atom2.aac')
        // <updated> used when <published> is missing
        ->and($two->published_at->getTimestamp())->toBe(strtotime('2024-01-08 10:00:00 UTC'))
        ->and($two->duration)->toBeNull();
});

// ----------------------------------------------------------------- duration

it('parses H:MM:SS durations', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>1:02:03</itunes:duration>'));

    expect($parser->listEpisodes()[0]->duration)->toBe(3723);
});

it('parses MM:SS durations', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>02:03</itunes:duration>'));

    expect($parser->listEpisodes()[0]->duration)->toBe(123);
});

it('parses plain seconds durations', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>45</itunes:duration>'));

    expect($parser->listEpisodes()[0]->duration)->toBe(45);
});

it('rejects durations below the 20s minimum', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>10</itunes:duration>'));

    expect($parser->listEpisodes()[0]->duration)->toBeNull();
});

it('treats durations over 86400 as bytes and converts at 128 kbps', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>196608000</itunes:duration>'));

    // 196608000 / 16384 = 12000 seconds
    expect($parser->listEpisodes()[0]->duration)->toBe(12000);
});

it('rejects byte sizes that convert below the minimum', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>90000</itunes:duration>'));

    // 90000 / 16384 = 5 seconds -> below the 20s minimum
    expect($parser->listEpisodes()[0]->duration)->toBeNull();
});

it('prefers numeric enclosure length over itunes:duration', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>30</itunes:duration>', 'length="32768000"'));

    // enclosure length wins and goes through the bytes heuristic: 2000s
    expect($parser->listEpisodes()[0]->duration)->toBe(2000);
});

it('falls back to itunes:duration for non-numeric enclosure length', function () {
    $parser = new FeedParser('https://example.com/feed');
    $parser->parse(rssWithDuration('<itunes:duration>30</itunes:duration>', 'length="abc"'));

    expect($parser->listEpisodes()[0]->duration)->toBe(30);
});

// ------------------------------------------------------ canonical URL rules

it('prefers itunes:new-feed-url over atom self and redirects', function () {
    $parser = new FeedParser('https://old.example.com/feed.xml');
    $parser->parse(rssDocument(
        '<itunes:new-feed-url>https://itunes-new.example.com/feed</itunes:new-feed-url>'
        .'<atom:link href="https://atom-self.example.com/feed" rel="self"/>'
    ), 'https://redirect.example.com/feed');

    expect($parser->feed_url)->toBe('https://itunes-new.example.com/feed')
        ->and($parser->getAliases())->toBe(['https://old.example.com/feed.xml']);
});

it('uses atom:link rel=self when no itunes:new-feed-url', function () {
    $parser = new FeedParser('https://old.example.com/feed.xml');
    $parser->parse(rssDocument(
        '<atom:link href="https://atom-self.example.com/feed" rel="self"/>'
    ), 'https://redirect.example.com/feed');

    expect($parser->feed_url)->toBe('https://atom-self.example.com/feed')
        ->and($parser->getAliases())->toBe(['https://old.example.com/feed.xml']);
});

it('falls back to the final redirect target', function () {
    $parser = new FeedParser('https://old.example.com/feed.xml');
    $parser->parse(rssDocument(''), 'https://redirect.example.com/feed');

    expect($parser->feed_url)->toBe('https://redirect.example.com/feed')
        ->and($parser->getAliases())->toBe(['https://old.example.com/feed.xml']);
});

it('keeps the requested url when the canonical candidate matches it', function () {
    $parser = new FeedParser('https://old.example.com/feed.xml');
    $parser->parse(rssDocument(
        '<atom:link href="https://old.example.com/feed.xml" rel="self"/>'
    ), 'https://old.example.com/feed.xml');

    expect($parser->feed_url)->toBe('https://old.example.com/feed.xml')
        ->and($parser->getAliases())->toBe([]);
});

// --------------------------------------------------------------- failures

it('rejects invalid xml', function () {
    $parser = new FeedParser('https://example.com/feed');

    expect($parser->parse('this is not xml'))->toBeFalse();
});

it('rejects documents without channel or entries', function () {
    $parser = new FeedParser('https://example.com/feed');

    expect($parser->parse('<html><body>x</body></html>'))->toBeFalse();
});

it('rejects feeds without a title', function () {
    $parser = new FeedParser('https://example.com/feed');

    expect($parser->parse('<?xml version="1.0"?><rss version="2.0"><channel></channel></rss>'))->toBeFalse();
});
