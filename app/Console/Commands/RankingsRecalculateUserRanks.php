<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RankingsRecalculateUserRanks extends Command
{
    protected $signature = 'rankings:recalculate-user-ranks';

    protected $description = 'Recalculate the stored global rank index for every ruleset.';

    private const TABLES = [
        'osu_user_stats',
        'osu_user_stats_taiko',
        'osu_user_stats_fruits',
        'osu_user_stats_mania',
        'osu_user_stats_mania_4k',
        'osu_user_stats_mania_7k',
    ];

    public function handle(): int
    {
        foreach (static::TABLES as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            DB::statement("UPDATE `{$table}` SET `rank_score_index` = 0 WHERE `rank_score` <= 0");

            DB::statement('SET @rank := 0');
            DB::statement(
                "UPDATE `{$table}` SET `rank_score_index` = (@rank := @rank + 1) "
                .'WHERE `rank_score` > 0 '
                .'ORDER BY `rank_score` DESC, `user_id` ASC'
            );

            $ranked = DB::table($table)->where('rank_score', '>', 0)->count();
            $this->line("{$table}: {$ranked} ranked");
        }

        return 0;
    }
}
