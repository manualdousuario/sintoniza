<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'last_activity_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_activity_at')->nullable()->index();
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['last_activity_at']);
            $table->dropColumn('last_activity_at');
        });
    }

    /**
     * Infers the last activity of existing users from what they left behind,
     * so the inactivity purge can act on them right away.
     */
    private function backfill(): void
    {
        /** @var array<int, int> $latest user_id => unix timestamp */
        $latest = [];

        $merge = function (int $userId, mixed $value) use (&$latest): void {
            if ($value === null || $value === '') {
                return;
            }

            $ts = is_numeric($value) ? (int) $value : Carbon::parse((string) $value, 'UTC')->getTimestamp();

            if ($ts > ($latest[$userId] ?? 0)) {
                $latest[$userId] = $ts;
            }
        };

        foreach (DB::table('users')->select('id', 'created_at', 'updated_at')->whereNull('last_activity_at')->cursor() as $user) {
            $merge((int) $user->id, $user->created_at);
            $merge((int) $user->id, $user->updated_at);
        }

        $sources = [
            ['episode_actions', 'changed_at'],
            ['subscriptions', 'updated_at'],
            ['devices', 'updated_at'],
            ['sessions', 'last_activity'],
        ];

        foreach ($sources as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $rows = DB::table($table)
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->select('user_id', DB::raw("MAX({$column}) as latest"))
                ->get();

            foreach ($rows as $row) {
                if (isset($latest[(int) $row->user_id])) {
                    $merge((int) $row->user_id, $row->latest);
                }
            }
        }

        foreach ($latest as $userId => $ts) {
            DB::table('users')
                ->where('id', $userId)
                ->whereNull('last_activity_at')
                ->update(['last_activity_at' => Carbon::createFromTimestamp($ts, 'UTC')->toDateTimeString()]);
        }
    }
};
