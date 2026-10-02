<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    const LOCAL_BEATMAPSET_ID_START = 10000000;
    const LOCAL_BEATMAP_ID_START = 14000000;

    public function up(): void
    {
        Schema::create('delta_mirror_beatmapsets', function (Blueprint $table) {
            $table->unsignedMediumInteger('beatmapset_id')->primary();
            $table->unsignedInteger('bancho_user_id');
            $table->string('creator', 80);
            $table->json('owners')->nullable();
            $table->string('fingerprint', 32);
            $table->timestamp('synced_at')->useCurrent();
        });

        DB::statement('ALTER TABLE `osu_beatmapsets` AUTO_INCREMENT = '.static::LOCAL_BEATMAPSET_ID_START);
        DB::statement('ALTER TABLE `osu_beatmaps` AUTO_INCREMENT = '.static::LOCAL_BEATMAP_ID_START);
    }

    public function down(): void
    {
        Schema::dropIfExists('delta_mirror_beatmapsets');
    }
};
