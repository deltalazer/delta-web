<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Console\Commands;

use App\Libraries\ChangelogReleases;
use Illuminate\Console\Command;

class ChangelogSyncReleases extends Command
{
    protected $signature = 'changelog:sync-releases';
    protected $description = 'Pull builds and changelog entries from GitHub releases';

    public function handle()
    {
        if (!ChangelogReleases::isConfigured()) {
            $this->error('No release repository configured');

            return static::FAILURE;
        }

        $this->line('Syncing releases of '.ChangelogReleases::repository());

        $count = ChangelogReleases::syncAll();

        $this->line("Done ({$count} releases)");

        return static::SUCCESS;
    }
}
