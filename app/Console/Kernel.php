<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('archive-forum-topics')
            ->cron('*/30 * * * *')
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('store:cleanup-stale-orders')
            ->daily()
            ->onOneServer();

        $schedule->command('store:expire-products')
            ->hourly()
            ->onOneServer();

        $schedule->command('builds:update-propagation-history')
            ->everyThirtyMinutes()
            ->onOneServer();

        $schedule->command('forum:topic-cover-cleanup --no-interaction')
            ->daily()
            ->onOneServer();

        $schedule->command('rankings:recalculate-user-ranks')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('delta:mirror-health')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('delta:mirror-import')
            ->dailyAt('04:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer();

        $schedule->command('delta:mirror-import --qualified-only')
            ->cron('15 1-23/2 * * *')
            ->withoutOverlapping()
            ->runInBackground()
            ->onOneServer();

        $schedule->command('rankings:recalculate-country-stats')
            ->cron('25 0,3,6,9,12,15,18,21 * * *')
            ->onOneServer();

        $schedule->command('rankings:recalculate-team-stats')
            ->cron('25 0,3,6,9,12,15,18,21 * * *')
            ->onOneServer();

        $schedule->command('rankings:recalculate-top-plays')
            ->cron('35 0,3,6,9,12,15,18,21 * * *')
            ->onOneServer();

        $schedule->command('modding:rank')
            ->cron('*/20 * * * *')
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('oauth:delete-expired-tokens')
            ->cron('14 1 * * *')
            ->onOneServer();

        // One row per minute: the landing graph samples every 10th row by id,
        // so a slower interval leaves it almost empty.
        $schedule->command('stats:update')
            ->everyMinute()
            ->withoutOverlapping(5)
            ->onOneServer();

        $schedule->command('news:sync')
            ->everyTenMinutes()
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('beatmap-leaders:refresh')
            ->everyThirtyMinutes()
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('github:sync-stargazers')
            ->everyTenMinutes()
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('changelog:sync-releases')
            ->everyTenMinutes()
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('notifications:news-published')
            ->everyThirtyMinutes()
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('notifications:send-mail')
            ->hourly()
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('user-notifications:cleanup')
            ->everyThirtyMinutes()
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('notifications:cleanup')
            ->cron('15,45 * * * *')
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('chat:expire-ack')
            ->everyFiveMinutes()
            ->withoutOverlapping(30)
            ->onOneServer();

        $schedule->command('daily-challenge:queue-random')
            ->cron('3 0 * * *')
            ->onOneServer();

        $schedule->command('daily-challenge:create-next')
            ->cron('5 0 * * *')
            ->onOneServer();

        $schedule->command('daily-challenge:user-stats-calculate')
            ->cron('10 0 * * *')
            ->onOneServer();

        $schedule->command('user-count-by-ruleset:recalculate')
            ->cron('20 0 * * *')
            ->withoutOverlapping(120)
            ->onOneServer();

        $schedule->command('ranked-play:decay-ratings')
            ->daily()
            ->onOneServer();
    }
}
