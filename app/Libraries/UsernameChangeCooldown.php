<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Libraries;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class UsernameChangeCooldown
{
    public static function availableAt(User $user): ?Carbon
    {
        $history = $user->usernameChangeHistory()
            ->paid()
            ->orderBy('timestamp', 'desc')
            ->get();

        $last = $history->first();

        return $last === null
            ? null
            : $last->timestamp->copy()->add(static::interval($history->count()));
    }

    private static function interval(int $changes): CarbonInterval
    {
        return match (true) {
            $changes < 2 => CarbonInterval::days(3),
            $changes === 2 => CarbonInterval::days(7),
            $changes === 3 => CarbonInterval::month(),
            default => CarbonInterval::months(3),
        };
    }
}
