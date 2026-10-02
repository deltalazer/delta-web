<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use App\Libraries\DeltaMirror;
use Illuminate\Console\Command;

class DeltaMirrorHealth extends Command
{
    protected $signature = 'delta:mirror-health';

    protected $description = 'Check which beatmap mirrors are reachable.';

    public function handle(): int
    {
        foreach (DeltaMirror::checkHealth() as $name => $up) {
            $this->line("{$name}: ".($up ? 'up' : 'down'));
        }

        return 0;
    }
}
