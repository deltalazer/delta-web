<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

use App\Models\Beatmapset;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const POOLS = [
        0 => 'osu!',
        1 => 'osu!taiko',
        2 => 'osu!catch',
        3 => 'osu!mania',
    ];

    public function up(): void
    {
        foreach (static::POOLS as $rulesetId => $name) {
            $playable = DB::table('osu_beatmaps')
                ->whereNull('deleted_at')
                ->where('playmode', $rulesetId)
                ->whereIn('approved', [Beatmapset::STATES['ranked'], Beatmapset::STATES['approved']])
                ->pluck('beatmap_id');

            foreach (['quick_play', 'ranked_play'] as $type) {
                $existing = DB::table('matchmaking_pools')
                    ->where(['ruleset_id' => $rulesetId, 'type' => $type])
                    ->value('id');

                if ($existing !== null) {
                    continue;
                }

                $poolId = DB::table('matchmaking_pools')->insertGetId([
                    'active' => $playable->isNotEmpty(),
                    'created_at' => now(),
                    'lobby_size' => $type === 'ranked_play' ? 2 : 8,
                    'name' => $name,
                    'ruleset_id' => $rulesetId,
                    'type' => $type,
                    'updated_at' => now(),
                    'variant_id' => 0,
                ]);

                foreach ($playable->chunk(200) as $chunk) {
                    DB::table('matchmaking_pool_beatmaps')->insert(
                        $chunk->map(fn ($beatmapId) => [
                            'beatmap_id' => $beatmapId,
                            'pool_id' => $poolId,
                        ])->all(),
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $poolIds = DB::table('matchmaking_pools')->pluck('id');

        DB::table('matchmaking_pool_beatmaps')->whereIn('pool_id', $poolIds)->delete();
        DB::table('matchmaking_pools')->whereIn('id', $poolIds)->delete();
    }
};
