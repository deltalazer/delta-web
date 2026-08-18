<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COLOURS = [
        'super_admin' => 'FF66AB',
        'admin' => 'EB5757',
        'gmt' => '59CD79',
        'nat' => '2FBFD8',
        'bng' => 'A347EB',
        'bng_limited' => 'A347EB',
        'dev' => 'F5A623',
        'alumni' => '999999',
        'loved' => 'FF66AA',
        'announce' => '9E9E9E',
        'bot' => '6E6E6E',
    ];

    public function up(): void
    {
        foreach (static::COLOURS as $identifier => $colour) {
            DB::table('phpbb_groups')
                ->where('identifier', $identifier)
                ->update([
                    'colour' => $colour,
                    'group_colour' => '',
                ]);
        }
    }

    public function down(): void
    {
        DB::table('phpbb_groups')
            ->whereIn('identifier', array_keys(static::COLOURS))
            ->update(['colour' => null]);
    }
};
