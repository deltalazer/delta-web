<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Console\Commands;

use App\Models\Beatmap;
use App\Models\Beatmapset;
use App\Models\Forum\Forum;
use App\Models\Forum\Post;
use App\Models\Forum\Topic;
use App\Models\User;
use DB;
use Illuminate\Console\Command;

class DbRemoveDemoData extends Command
{
    const USER_REFERENCES = [
        ['osu_events', 'user_id'],
        ['osu_favouritemaps', 'user_id'],
        ['osu_kudos_exchange', 'giver_id'],
        ['osu_kudos_exchange', 'receiver_id'],
        ['osu_leaders', 'user_id'],
        ['osu_leaders_fruits', 'user_id'],
        ['osu_leaders_mania', 'user_id'],
        ['osu_leaders_taiko', 'user_id'],
        ['osu_scores', 'user_id'],
        ['osu_scores_fruits', 'user_id'],
        ['osu_scores_fruits_high', 'user_id'],
        ['osu_scores_high', 'user_id'],
        ['osu_scores_mania', 'user_id'],
        ['osu_scores_mania_high', 'user_id'],
        ['osu_scores_taiko', 'user_id'],
        ['osu_scores_taiko_high', 'user_id'],
        ['osu_user_achievements', 'user_id'],
        ['osu_user_banhistory', 'user_id'],
        ['osu_user_beatmap_playcount', 'user_id'],
        ['osu_user_month_playcount', 'user_id'],
        ['osu_user_performance_rank', 'user_id'],
        ['osu_user_replayswatched', 'user_id'],
        ['osu_user_stats', 'user_id'],
        ['osu_user_stats_fruits', 'user_id'],
        ['osu_user_stats_mania', 'user_id'],
        ['osu_user_stats_mania_4k', 'user_id'],
        ['osu_user_stats_mania_7k', 'user_id'],
        ['osu_user_stats_taiko', 'user_id'],
        ['osu_username_change_history', 'user_id'],
        ['phpbb_user_group', 'user_id'],
        ['phpbb_zebra', 'user_id'],
        ['phpbb_zebra', 'zebra_id'],
    ];

    protected $signature = 'db:remove-demo-data {--force}';
    protected $description = 'Remove the accounts, forum threads and beatmaps created by db:seed';

    public function handle()
    {
        $userIds = User::where([
            'user_twitter' => 'ppy',
            'user_website' => 'http://www.google.com/',
        ])->pluck('user_id');

        if ($userIds->isEmpty()) {
            $this->line('No demo accounts found');

            return static::SUCCESS;
        }

        $count = $userIds->count();

        if (!$this->option('force') && !$this->confirm("Remove {$count} demo accounts and everything they created?")) {
            return static::FAILURE;
        }

        DB::transaction(function () use ($userIds) {
            Post::whereIn('poster_id', $userIds)->forceDelete();
            Topic::whereIn('topic_poster', $userIds)->forceDelete();

            $beatmapsetIds = Beatmapset::whereIn('user_id', $userIds)->pluck('beatmapset_id');
            Beatmap::whereIn('beatmapset_id', $beatmapsetIds)->forceDelete();
            Beatmapset::whereIn('beatmapset_id', $beatmapsetIds)->forceDelete();

            foreach (static::USER_REFERENCES as [$table, $column]) {
                DB::table($table)->whereIn($column, $userIds)->delete();
            }

            User::whereIn('user_id', $userIds)->delete();
        });

        Forum::all()->each(fn (Forum $forum) => $forum->refreshCache());

        $this->line("Removed {$count} demo accounts");

        return static::SUCCESS;
    }
}
