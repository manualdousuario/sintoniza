<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneEpisodeActions extends Command
{
    protected $signature = 'sintoniza:prune-episode-actions
                            {--dry-run : Count the actions that would be removed without deleting anything}';

    protected $description = 'Keep only the latest episode action per episode and device';

    private const DELETE_CHUNK = 1000;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $total = 0;

        foreach (User::query()->select('id')->lazyById() as $user) {
            $ids = $this->supersededIds((int) $user->id);

            if ($ids === []) {
                continue;
            }

            $total += count($ids);

            if (! $dryRun) {
                foreach (array_chunk($ids, self::DELETE_CHUNK) as $chunk) {
                    DB::table('episode_actions')->whereIn('id', $chunk)->delete();
                }
            }
        }

        if (! $dryRun && $total > 0) {
            Log::info('Pruned superseded episode actions', ['count' => $total]);
        }

        $this->info(sprintf('Done: %d episode action(s) %s', $total, $dryRun ? 'would be removed' : 'removed'));

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function supersededIds(int $userId): array
    {
        $ranked = DB::table('episode_actions')
            ->where('user_id', $userId)
            ->select('id')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY COALESCE(device_id, 0), url ORDER BY changed_at DESC, id DESC) AS position');

        return DB::query()
            ->fromSub($ranked, 'ranked')
            ->where('position', '>', 1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
