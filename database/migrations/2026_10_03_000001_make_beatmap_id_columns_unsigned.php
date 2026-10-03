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
        DB::statement('ALTER TABLE `solo_score_tokens` MODIFY `beatmap_id` MEDIUMINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `osu_beatmap_failtimes` MODIFY `beatmap_id` MEDIUMINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `osu_user_achievements` MODIFY `beatmap_id` MEDIUMINT UNSIGNED NULL DEFAULT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `solo_score_tokens` MODIFY `beatmap_id` MEDIUMINT NOT NULL');
        DB::statement('ALTER TABLE `osu_beatmap_failtimes` MODIFY `beatmap_id` MEDIUMINT NOT NULL');
        DB::statement('ALTER TABLE `osu_user_achievements` MODIFY `beatmap_id` MEDIUMINT NULL DEFAULT NULL');
    }
};
