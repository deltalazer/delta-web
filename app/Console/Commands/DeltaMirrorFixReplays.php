<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use App\Libraries\DeltaMirror;
use App\Models\Beatmap;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeltaMirrorFixReplays extends Command
{
    const BACKUP_DIRECTORY = 'pre-mirror';

    protected $signature = 'delta:mirror-fix-replays {--apply : Rewrite the beatmap hash in those replays}';

    protected $description = 'Point replays of scores moved onto mirrored beatmaps at the mirrored beatmap, so the client can import them.';

    public function handle(): int
    {
        $disk = storage_disk('replay');
        $scores = DB::table('scores as c')
            ->join('osu_beatmaps as b', 'b.beatmap_id', '=', 'c.beatmap_id')
            ->join('osu_beatmapsets as s', 's.beatmapset_id', '=', 'b.beatmapset_id')
            ->where('c.has_replay', true)
            ->where('s.user_id', 0)
            ->orderBy('c.id')
            ->get(['c.id', 'c.beatmap_id', 'b.checksum']);

        $fixes = [];
        $currentHashes = [];

        foreach ($scores as $score) {
            $data = $disk->get((string) $score->id);
            $hash = $data === null ? null : static::beatmapHash($data);

            if ($hash === null) {
                continue;
            }

            if (!array_key_exists($score->beatmap_id, $currentHashes)) {
                $path = DeltaMirror::osuFile(Beatmap::find($score->beatmap_id));
                $currentHashes[$score->beatmap_id] = $path === null ? null : md5_file($path);
            }

            $target = $currentHashes[$score->beatmap_id];

            if ($target === null) {
                $this->warn("score {$score->id} on beatmap {$score->beatmap_id}: the current beatmap file could not be fetched, skipped");
                continue;
            }

            if ($hash === $target) {
                continue;
            }

            $fixes[] = [$score, $data, $target];
            $this->line("score {$score->id} on beatmap {$score->beatmap_id}: {$hash} -> {$target}".($target === $score->checksum ? '' : ' (newer than the stored checksum)'));
        }

        $this->info(count($fixes).' of '.count($scores).' replays on mirrored beatmaps point at a different beatmap version');

        if (!$this->option('apply')) {
            $this->info('nothing changed - run again with --apply to rewrite them (originals are kept in '.static::BACKUP_DIRECTORY.'/)');

            return 0;
        }

        $failed = 0;

        foreach ($fixes as [$score, $data, $target]) {
            $backup = static::BACKUP_DIRECTORY."/{$score->id}";
            $temporary = "{$score->id}.delta-tmp";

            if (!$disk->exists($backup) && !$disk->put($backup, $data)) {
                $this->error("score {$score->id}: could not write the backup, left unchanged");
                $failed++;
                continue;
            }

            if (!$disk->put($temporary, substr_replace($data, $target, 7, 32)) || !$disk->move($temporary, (string) $score->id)) {
                $disk->delete($temporary);
                $this->error("score {$score->id}: could not write the replay, left unchanged");
                $failed++;
                continue;
            }
        }

        $this->info('rewrote '.(count($fixes) - $failed).' replays'.($failed > 0 ? ", {$failed} failed" : ''));

        return $failed > 0 ? 1 : 0;
    }

    private static function beatmapHash(string $data): ?string
    {
        return strlen($data) >= 39 && $data[5] === "\x0b" && $data[6] === "\x20"
            ? substr($data, 7, 32)
            : null;
    }
}
