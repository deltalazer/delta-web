<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeltaMirrorRecountStats extends Command
{
    const CHUNK_SIZE = 100000;

    protected $signature = 'delta:mirror-recount-stats {--apply : Replace the bancho counts with this server\'s own}';

    protected $description = 'Replace the bancho play, pass, favourite and rating counts on mirrored beatmapsets with this server\'s own.';

    public function handle(): int
    {
        $current = DB::selectOne(
            'SELECT COUNT(*) `sets`, COALESCE(SUM(`play_count`), 0) `plays`, COALESCE(SUM(`favourite_count`), 0) `favourites`, COALESCE(SUM(`rating` > 0), 0) `rated` '
            .'FROM `osu_beatmapsets` WHERE `user_id` = 0',
        );
        $currentPasses = DB::selectOne(
            'SELECT COALESCE(SUM(b.`passcount`), 0) `passes` FROM `osu_beatmaps` b '
            .'JOIN `osu_beatmapsets` s ON s.`beatmapset_id` = b.`beatmapset_id` WHERE s.`user_id` = 0',
        );
        $local = DB::selectOne(
            'SELECT '
            .'(SELECT COALESCE(SUM(p.`playcount`), 0) FROM `osu_user_beatmap_playcount` p JOIN `osu_beatmaps` b ON b.`beatmap_id` = p.`beatmap_id` JOIN `osu_beatmapsets` s ON s.`beatmapset_id` = b.`beatmapset_id` WHERE s.`user_id` = 0) `plays`, '
            .'(SELECT COUNT(*) FROM `scores` c JOIN `osu_beatmaps` b ON b.`beatmap_id` = c.`beatmap_id` JOIN `osu_beatmapsets` s ON s.`beatmapset_id` = b.`beatmapset_id` WHERE s.`user_id` = 0 AND c.`passed` = 1) `passes`, '
            .'(SELECT COUNT(*) FROM `osu_favouritemaps` f JOIN `osu_beatmapsets` s ON s.`beatmapset_id` = f.`beatmapset_id` WHERE s.`user_id` = 0) `favourites`, '
            .'(SELECT COUNT(DISTINCT r.`beatmapset_id`) FROM `osu_user_beatmapset_ratings` r JOIN `osu_beatmapsets` s ON s.`beatmapset_id` = r.`beatmapset_id` WHERE s.`user_id` = 0) `rated`',
        );

        $this->line("mirrored sets: {$current->sets}");
        $this->line('plays:      '.number_format((int) $current->plays).' -> '.number_format((int) $local->plays));
        $this->line('passes:     '.number_format((int) $currentPasses->passes).' -> at most '.number_format((int) $local->passes));
        $this->line('favourites: '.number_format((int) $current->favourites).' -> '.number_format((int) $local->favourites));
        $this->line('rated sets: '.number_format((int) $current->rated).' -> '.number_format((int) $local->rated));

        $examples = DB::select(
            'SELECT s.`beatmapset_id`, s.`artist`, s.`title`, s.`play_count`, s.`favourite_count`, SUM(p.`playcount`) `local_plays` '
            .'FROM `osu_user_beatmap_playcount` p '
            .'JOIN `osu_beatmaps` b ON b.`beatmap_id` = p.`beatmap_id` '
            .'JOIN `osu_beatmapsets` s ON s.`beatmapset_id` = b.`beatmapset_id` '
            .'WHERE s.`user_id` = 0 GROUP BY s.`beatmapset_id` ORDER BY `local_plays` DESC LIMIT 10',
        );

        foreach ($examples as $set) {
            $favourites = DB::table('osu_favouritemaps')->where('beatmapset_id', $set->beatmapset_id)->count();
            $this->line("  set {$set->beatmapset_id} {$set->artist} - {$set->title}: plays "
                .number_format((int) $set->play_count)." -> {$set->local_plays}, favourites "
                .number_format((int) $set->favourite_count)." -> {$favourites}");
        }

        if (!$this->option('apply')) {
            $this->info('nothing changed - run again with --apply to replace the counts');

            return 0;
        }

        $maxId = (int) DB::table('osu_beatmapsets')->where('user_id', 0)->max('beatmapset_id');

        for ($from = 0; $from <= $maxId; $from += static::CHUNK_SIZE) {
            $to = $from + static::CHUNK_SIZE;

            DB::update(
                'UPDATE `osu_beatmaps` b '
                .'JOIN `osu_beatmapsets` s ON s.`beatmapset_id` = b.`beatmapset_id` AND s.`user_id` = 0 '
                .'LEFT JOIN (SELECT `beatmap_id`, SUM(`playcount`) `n` FROM `osu_user_beatmap_playcount` GROUP BY `beatmap_id`) p ON p.`beatmap_id` = b.`beatmap_id` '
                .'LEFT JOIN (SELECT `beatmap_id`, COUNT(*) `n` FROM `scores` WHERE `passed` = 1 GROUP BY `beatmap_id`) q ON q.`beatmap_id` = b.`beatmap_id` '
                .'SET b.`playcount` = COALESCE(p.`n`, 0), b.`passcount` = LEAST(COALESCE(q.`n`, 0), COALESCE(p.`n`, 0)) '
                .'WHERE b.`beatmapset_id` >= ? AND b.`beatmapset_id` < ?',
                [$from, $to],
            );

            DB::update(
                'UPDATE `osu_beatmapsets` s '
                .'LEFT JOIN (SELECT `beatmapset_id`, SUM(`playcount`) `n` FROM `osu_beatmaps` WHERE `beatmapset_id` >= ? AND `beatmapset_id` < ? GROUP BY `beatmapset_id`) p ON p.`beatmapset_id` = s.`beatmapset_id` '
                .'LEFT JOIN (SELECT `beatmapset_id`, COUNT(*) `n` FROM `osu_favouritemaps` GROUP BY `beatmapset_id`) f ON f.`beatmapset_id` = s.`beatmapset_id` '
                .'LEFT JOIN (SELECT `beatmapset_id`, AVG(`rating`) `n` FROM `osu_user_beatmapset_ratings` GROUP BY `beatmapset_id`) r ON r.`beatmapset_id` = s.`beatmapset_id` '
                .'SET s.`play_count` = COALESCE(p.`n`, 0), s.`favourite_count` = COALESCE(f.`n`, 0), s.`rating` = COALESCE(r.`n`, 0) '
                .'WHERE s.`user_id` = 0 AND s.`beatmapset_id` >= ? AND s.`beatmapset_id` < ?',
                [$from, $to, $from, $to],
            );

            $this->line('recounted sets '.number_format($from).' - '.number_format(min($to, $maxId)));
        }

        $this->info('done - rebuild the search index so sorting by plays and favourites uses the new counts: es:index-documents --types=beatmapsets --cleanup --no-interaction');

        return 0;
    }
}
