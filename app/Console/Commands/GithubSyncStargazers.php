<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Console\Commands;

use App\Libraries\GithubStars;
use Illuminate\Console\Command;

class GithubSyncStargazers extends Command
{
    protected $signature = 'github:sync-stargazers';
    protected $description = 'Refresh supporter status from the repository stargazer list';

    public function handle()
    {
        if (!GithubStars::isConfigured()) {
            $this->error('No supporter repository configured');

            return static::FAILURE;
        }

        $this->line('Syncing stargazers of '.GithubStars::repository());

        $count = GithubStars::syncAll();

        $this->line("Done ({$count} stargazers)");

        return static::SUCCESS;
    }
}
