<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PurgeInactiveUsers extends Command
{
    protected $signature = 'sintoniza:purge-inactive-users
                            {--months= : Inactivity threshold in months (default: sintoniza.inactive_user_months)}
                            {--dry-run : List the users that would be removed without deleting anything}';

    protected $description = 'Delete non-admin users (and all their data) inactive for the configured period';

    public function handle(UserService $users): int
    {
        $months = (int) ($this->option('months') ?? config('sintoniza.inactive_user_months'));

        if ($months <= 0) {
            $this->info('Inactive user purge is disabled.');

            return self::SUCCESS;
        }

        $cutoff = now()->subMonths($months);
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        $this->info(sprintf('Sintoniza — %s users inactive since %s...', $dryRun ? 'listing' : 'removing', $cutoff->toDateTimeString()));

        foreach ($users->inactiveUsersQuery($cutoff)->lazyById() as $user) {
            $lastSeen = ($user->last_activity_at ?? $user->created_at)?->toDateTimeString();

            if ($dryRun) {
                $this->line(sprintf('  %s (last activity: %s)', $user->name, $lastSeen));
            } else {
                $users->purge($user);
                Log::info('Purged inactive user', ['user_id' => $user->id, 'last_activity_at' => $lastSeen]);
            }

            $count++;
        }

        $this->info(sprintf('Done: %d user(s) %s', $count, $dryRun ? 'would be removed' : 'removed'));

        return self::SUCCESS;
    }
}
