<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Console\Commands;

use App\Models\NewsPost;
use Illuminate\Console\Command;

class NewsSync extends Command
{
    protected $signature = 'news:sync';
    protected $description = 'Pull news posts from the wiki repository';

    public function handle()
    {
        $this->line('Syncing news posts');

        NewsPost::syncAll();

        $this->line('Done');
    }
}
