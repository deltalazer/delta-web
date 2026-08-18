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
        DB::table('osu_mirrors')->updateOrInsert(
            ['mirror_id' => 1],
            [
                'base_url' => presence(env('BEATMAP_MIRROR_URL')) ?? rtrim($GLOBALS['cfg']['app']['url'], '/').'/',
                'enabled' => 1,
                'provider_user_id' => 1,
                'traffic_limit' => 0,
                'traffic_used' => 0,
                'version' => 2,
            ],
        );
    }

    public function down(): void
    {
        DB::table('osu_mirrors')->where('mirror_id', 1)->delete();
    }
};
