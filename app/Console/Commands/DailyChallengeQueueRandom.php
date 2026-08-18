<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Beatmap;
use App\Models\Beatmapset;
use App\Models\Multiplayer\DailyChallengeQueueItem;
use Illuminate\Console\Command;

class DailyChallengeQueueRandom extends Command
{
    protected $signature = 'daily-challenge:queue-random {--count=1} {--force}';

    protected $description = 'Queue randomly picked beatmaps for the daily challenge.';

    private const LOVED_CHANCE = 40;

    private const RANKED_STATES = [Beatmapset::STATES['ranked'], Beatmapset::STATES['approved']];

    private const LOVED_STATES = [Beatmapset::STATES['loved']];

    public function handle(): int
    {
        $pending = DailyChallengeQueueItem::whereNull('multiplayer_room_id')->count();

        if ($pending > 0 && !$this->option('force')) {
            $this->line("queue already has {$pending} pending item(s)");

            return 0;
        }

        $count = max(1, get_int($this->option('count')) ?? 1);
        $queued = 0;

        for ($i = 0; $i < $count; $i++) {
            $beatmap = $this->pickBeatmap();

            if ($beatmap === null) {
                $this->error('no eligible beatmap found');

                return $queued > 0 ? 0 : 1;
            }

            $item = new DailyChallengeQueueItem();
            $item->beatmap_id = $beatmap->getKey();
            $item->ruleset_id = $beatmap->playmode;
            $item->save();

            $queued++;

            $set = $beatmap->beatmapset;
            $state = array_search($beatmap->approved, Beatmapset::STATES, true);
            $this->info("queued {$beatmap->getKey()} ({$state}): {$set->artist} - {$set->title} [{$beatmap->version}]");
        }

        return 0;
    }

    private function pickBeatmap(): ?Beatmap
    {
        $preferLoved = random_int(1, 100) <= static::LOVED_CHANCE;

        return $preferLoved
            ? $this->randomFrom(static::LOVED_STATES) ?? $this->randomFrom(static::RANKED_STATES)
            : $this->randomFrom(static::RANKED_STATES) ?? $this->randomFrom(static::LOVED_STATES);
    }

    private function randomFrom(array $states): ?Beatmap
    {
        return Beatmap::whereIn('approved', $states)
            ->where('playmode', 0)
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->first();
    }
}
