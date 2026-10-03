<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Beatmap;
use App\Models\Count;
use App\Models\UserStatistics\Model as UserStatistics;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeltaRankHistorySnapshot extends Command
{
    const DAYS = 90;

    protected $signature = 'delta:rank-history-snapshot';

    protected $description = 'Record every ranked player\'s global rank for the day and move rank history on to the next day.';

    public function handle(): int
    {
        foreach (Beatmap::MODES as $ruleset => $mode) {
            $table = (new (UserStatistics::getClass($ruleset)))->getTable();
            $counter = Count::currentRankStartName($ruleset);
            $today = (int) (DB::table('osu_counts')->where('name', $counter)->value('count') ?? 0) % static::DAYS;
            $next = ($today + 1) % static::DAYS;

            DB::transaction(function () use ($counter, $mode, $next, $table, $today) {
                DB::table('osu_user_performance_rank')->where('mode', $mode)->update(["r{$today}" => 0]);
                DB::statement(
                    "INSERT INTO `osu_user_performance_rank` (`user_id`, `mode`, `r{$today}`) "
                    ."SELECT `s`.`user_id`, ?, `s`.`rank_score_index` FROM `{$table}` `s` WHERE `s`.`rank_score_index` > 0 "
                    ."ON DUPLICATE KEY UPDATE `r{$today}` = `s`.`rank_score_index`",
                    [$mode],
                );
                DB::table('osu_user_performance_rank')->where('mode', $mode)->update(["r{$next}" => 0]);
                DB::table('osu_counts')->upsert([['name' => $counter, 'count' => $next]], ['name'], ['count']);
            });

            $ranked = DB::table($table)->where('rank_score_index', '>', 0)->count();
            $this->line("{$ruleset}: recorded {$ranked} ranks in r{$today}, today is now r{$next}");
        }

        return 0;
    }
}
