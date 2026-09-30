<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $medals = json_decode(
            file_get_contents(database_path('data/medals.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $ids = array_column($medals, 'id');

        DB::transaction(function () use ($medals, $ids) {
            $stale = DB::table('osu_achievements')->whereNotIn('achievement_id', $ids)->pluck('achievement_id');

            DB::table('osu_user_achievements')->whereIn('achievement_id', $stale)->delete();
            DB::table('osu_achievements')->whereIn('achievement_id', $stale)->delete();

            foreach (array_chunk($medals, 100) as $chunk) {
                DB::table('osu_achievements')->upsert(
                    array_map(fn (array $medal) => [
                        'achievement_id' => $medal['id'],
                        'description' => $medal['description'],
                        'enabled' => 1,
                        'grouping' => $medal['grouping'],
                        'mode' => $medal['mode'],
                        'name' => $medal['name'],
                        'ordering' => $medal['ordering'],
                        'progression' => 0,
                        'quest_instructions' => $medal['instructions'],
                        'slug' => $medal['slug'],
                    ], $chunk),
                    ['achievement_id'],
                    ['description', 'enabled', 'grouping', 'mode', 'name', 'ordering', 'quest_instructions', 'slug'],
                );
            }
        });
    }

    public function down(): void
    {
    }
};
