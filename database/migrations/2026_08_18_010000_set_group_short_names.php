<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SHORT_NAMES = [
        'super_admin' => 'OWN',
        'admin' => 'ADM',
        'gmt' => 'GMT',
        'nat' => 'NAT',
        'bng' => 'BN',
        'bng_limited' => 'BN',
        'dev' => 'DEV',
        'alumni' => 'ALM',
        'loved' => 'LVD',
        'announce' => 'ANN',
        'bot' => 'BOT',
    ];

    public function up(): void
    {
        foreach (static::SHORT_NAMES as $identifier => $shortName) {
            DB::table('phpbb_groups')
                ->where('identifier', $identifier)
                ->update(['short_name' => $shortName]);
        }
    }

    public function down(): void
    {
        foreach (array_keys(static::SHORT_NAMES) as $identifier) {
            DB::table('phpbb_groups')
                ->where('identifier', $identifier)
                ->update(['short_name' => $identifier]);
        }
    }
};
