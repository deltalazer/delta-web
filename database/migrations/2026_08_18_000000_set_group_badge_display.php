<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const GROUPS = [
        'super_admin' => [1, 'FF66AB'],
        'admin' => [2, 'EB5757'],
        'gmt' => [3, '59CD79'],
        'nat' => [4, '2FBFD8'],
        'bng' => [5, 'A347EB'],
        'bng_limited' => [6, 'A347EB'],
        'dev' => [7, 'F5A623'],
        'alumni' => [8, '999999'],
        'loved' => [9, 'FF66AA'],
        'announce' => [10, '9E9E9E'],
        'bot' => [11, '6E6E6E'],
    ];

    public function up(): void
    {
        foreach (static::GROUPS as $identifier => [$order, $colour]) {
            DB::table('phpbb_groups')
                ->where('identifier', $identifier)
                ->update([
                    'display_order' => $order,
                    'group_colour' => $colour,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('phpbb_groups')
            ->whereIn('identifier', array_keys(static::GROUPS))
            ->update([
                'display_order' => null,
                'group_colour' => '',
            ]);
    }
};
