<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropColumn('subscriptions', 'feed_id', 'subscription_feed');
        $this->dropColumn('episode_actions', 'episode_id', 'episodes_actions_link');

        Schema::dropIfExists('episodes');
        Schema::dropIfExists('feed_aliases');
        Schema::dropIfExists('feeds');
    }

    public function down(): void
    {
        foreach (['subscriptions' => 'feed_id', 'episode_actions' => 'episode_id'] as $table => $column) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, $column)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->integer($column)->nullable();
                });
            }
        }
    }

    private function dropColumn(string $table, string $column, string $index): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        $foreignKeys = collect(Schema::getForeignKeys($table))
            ->filter(fn (array $fk) => $fk['columns'] === [$column]);

        foreach ($foreignKeys as $fk) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($fk['name'] ?: [$column]));
        }

        if (Schema::hasIndex($table, $index)) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index));
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn($column));
    }
};
