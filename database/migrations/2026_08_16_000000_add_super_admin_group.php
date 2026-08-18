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
        DB::table('phpbb_groups')->insertOrIgnore([
            'group_desc' => '',
            'group_name' => 'super_admin',
            'group_type' => 2,
            'has_playmodes' => false,
            'identifier' => 'super_admin',
            'short_name' => 'super_admin',
        ]);
    }

    public function down(): void
    {
        DB::table('phpbb_groups')->where('identifier', 'super_admin')->delete();
    }
};
