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
        $userId = (int) ($GLOBALS['cfg']['osu']['legacy']['bancho_bot_user_id'] ?? 3);

        if (DB::table('phpbb_users')->where('user_id', $userId)->exists()) {
            return;
        }

        $botGroupId = DB::table('phpbb_groups')->where('identifier', 'bot')->value('group_id');

        if ($botGroupId === null) {
            throw new Exception('bot group is missing');
        }

        DB::table('phpbb_users')->insert([
            'user_id' => $userId,
            'username' => 'DeltaBot',
            'username_clean' => 'deltabot',
            'group_id' => $botGroupId,
            'user_permissions' => '',
            'user_sig' => '',
            'user_occ' => '',
            'user_interests' => '',
            'user_regdate' => time(),
            'country_acronym' => 'US',
        ]);

        DB::table('phpbb_user_group')->insertOrIgnore([
            'group_id' => $botGroupId,
            'user_id' => $userId,
            'user_pending' => 0,
        ]);
    }

    public function down(): void
    {
        $userId = (int) ($GLOBALS['cfg']['osu']['legacy']['bancho_bot_user_id'] ?? 3);

        DB::table('phpbb_user_group')->where('user_id', $userId)->delete();
        DB::table('phpbb_users')->where('user_id', $userId)->delete();
    }
};
