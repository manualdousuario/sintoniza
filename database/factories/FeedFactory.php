<?php

namespace Database\Factories;

use App\Models\Feed;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Feed>
 */
class FeedFactory extends Factory
{
    public function definition(): array
    {
        $words = fake()->words(3, true);

        return [
            'feed_url' => 'https://example.com/'.fake()->slug().'/feed.xml',
            'title' => ucfirst($words),
            'description' => fake()->sentence(12),
            'url' => 'https://example.com/'.fake()->slug(),
            'image_url' => 'https://example.com/cover.jpg',
            'language' => fake()->randomElement(['en', 'es', 'pt-BR']),
            'published_at' => Carbon::now()->subDays(rand(1, 20)),
        ];
    }
}
