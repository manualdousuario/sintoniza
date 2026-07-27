<?php

namespace Database\Factories;

use App\Models\Episode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Episode>
 */
class EpisodeFactory extends Factory
{
    public function definition(): array
    {
        $id = fake()->uuid();

        return [
            'media_url' => 'https://cdn.example.com/ep/'.$id.'.mp3',
            'url' => 'https://example.com/ep/'.$id,
            'image_url' => 'https://example.com/cover.jpg',
            'duration' => fake()->numberBetween(600, 5400),
            'title' => ucfirst(fake()->words(4, true)),
            'description' => fake()->paragraph(3),
            'published_at' => Carbon::now()->subDays(rand(1, 30)),
        ];
    }
}
