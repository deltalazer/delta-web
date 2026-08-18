<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('osu_beatmap_scoring_attribs', function (Blueprint $table): void {
            $table->unsignedMediumInteger('beatmap_id');
            $table->unsignedTinyInteger('mode');
            $table->integer('legacy_accuracy_score');
            $table->bigInteger('legacy_combo_score');
            $table->double('legacy_bonus_score_ratio');
            $table->integer('legacy_bonus_score');
            $table->integer('max_combo');
            $table->primary(['beatmap_id', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::drop('osu_beatmap_scoring_attribs');
    }
};
