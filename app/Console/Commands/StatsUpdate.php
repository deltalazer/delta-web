<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Console\Commands;

use App\Models\BanchoStats;
use App\Models\Count;
use App\Models\User;
use Illuminate\Console\Command;

class StatsUpdate extends Command
{
    protected $signature = 'stats:update';
    protected $description = 'Refresh the registered user count and record current online players';

    public function handle()
    {
        $total = User::count();
        Count::totalUsers()->update(['count' => $total]);

        $online = User::online()->count();

        BanchoStats::create([
            'users_lazer' => $online,
            'multiplayer_games_lazer' => 0,
        ]);

        $this->line("{$total} registered, {$online} online");
    }
}
