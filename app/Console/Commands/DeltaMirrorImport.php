<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\EsDocument;
use App\Libraries\DeltaMirror;
use App\Models\Beatmapset;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeltaMirrorImport extends Command
{
    const CURRENT_SET_DELAY_US = 700000;
    const CURRENT_SET_URL = 'https://osu.direct/api/v2/s/%d';
    const LOCAL_BEATMAP_STATS = ['playcount', 'passcount'];
    const LOCAL_SET_STATS = ['favourite_count', 'play_count', 'rating'];
    const PAGE_SIZE = 500;
    const SEARCH_URL = 'https://catboy.best/api/v2/search';
    const STATUSES = [1, 2, 3, 4];

    protected $signature = 'delta:mirror-import
        {--full : Page through every set instead of stopping at the first page with nothing new}
        {--max-pages=0 : Stop each status after this many pages}
        {--dry-run : Fetch and check everything without writing}
        {--qualified-only : Only re-check the qualified sets against osu.direct}';

    protected $description = 'Import ranked, approved, qualified and loved beatmapsets from the beatmap mirror.';

    private bool $dryRun;
    private int $skipped = 0;
    private int $stale = 0;

    public function handle(): int
    {
        $client = DeltaMirror::client();
        $full = (bool) $this->option('full');
        $maxPages = get_int($this->option('max-pages')) ?? 0;
        $this->dryRun = (bool) $this->option('dry-run');
        $changed = [];

        foreach ($this->option('qualified-only') ? [] : static::STATUSES as $status) {
            for ($page = 0; $maxPages === 0 || $page < $maxPages; $page++) {
                $offset = $page * static::PAGE_SIZE;
                $sets = $this->fetchPage($client, $status, $offset);

                if ($sets === null) {
                    $this->error("the mirror did not answer for status {$status} at offset {$offset}");

                    return 1;
                }

                if ($sets === []) {
                    break;
                }

                $pageChanged = 0;

                foreach ($sets as $set) {
                    if ($this->importSet($set)) {
                        $changed[] = (int) $set['id'];
                        $pageChanged++;
                    }
                }

                $this->line("status {$status}, offset {$offset}: ".count($sets)." sets, {$pageChanged} new or updated");

                if (!$full && $pageChanged === 0) {
                    break;
                }

                usleep(500000);
            }
        }

        $refreshed = $this->refreshQualified($client);

        if (!$this->dryRun) {
            foreach (array_chunk(array_values(array_unique($full ? $refreshed : [...$changed, ...$refreshed])), 200) as $ids) {
                foreach (Beatmapset::whereIn('beatmapset_id', $ids)->get() as $beatmapset) {
                    dispatch(new EsDocument($beatmapset));
                }
            }
        }

        $this->info(count($changed).' sets '.($this->dryRun ? 'would be' : 'were').' imported or updated, '.count($refreshed).' qualified sets corrected, '
            .$this->skipped.' skipped, '.$this->stale.' ignored because the mirror had older data than what is stored');

        if ($full && !$this->dryRun) {
            $this->info('run es:index-documents --types=beatmapsets to make them searchable');
        }

        return 0;
    }

    private static function cut(?string $value, int $length): ?string
    {
        return $value === null ? null : mb_substr($value, 0, $length);
    }

    private static function time(?string $value): ?Carbon
    {
        return present($value) ? Carbon::parse($value) : null;
    }

    private function fetchPage(Client $client, int $status, int $offset): ?array
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $client->get(static::SEARCH_URL, ['query' => [
                    'limit' => static::PAGE_SIZE,
                    'offset' => $offset,
                    'status' => $status,
                ]]);

                $sets = json_decode((string) $response->getBody(), true);

                if (is_array($sets)) {
                    return $sets;
                }
            } catch (Throwable $e) {
                $this->warn("attempt {$attempt} failed: {$e->getMessage()}");
            }

            sleep(5 * $attempt);
        }

        return null;
    }

    private function fetchCurrentSet(Client $client, int $id): ?array
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $client->get(sprintf(static::CURRENT_SET_URL, $id), ['http_errors' => false]);
                $status = $response->getStatusCode();

                if ($status === 404) {
                    $this->warn("set {$id}: osu.direct does not know it any more, left unchanged");

                    return null;
                }

                if ($status === 429) {
                    sleep(max(1, (int) $response->getHeaderLine('ratelimit-reset')));
                    continue;
                }

                $set = json_decode((string) $response->getBody(), true);

                if ($status === 200 && is_array($set) && isset($set['beatmaps'])) {
                    return $set;
                }
            } catch (Throwable $e) {
                $this->warn("set {$id}: attempt {$attempt} failed: {$e->getMessage()}");
            }

            sleep(2 * $attempt);
        }

        $this->warn("set {$id}: osu.direct did not answer, left unchanged");

        return null;
    }

    private function refreshQualified(Client $client): array
    {
        $ids = DB::table('osu_beatmapsets')
            ->where('user_id', 0)
            ->where('approved', Beatmapset::STATES['qualified'])
            ->orderBy('beatmapset_id')
            ->pluck('beatmapset_id');
        $changed = [];

        foreach ($ids as $id) {
            $set = $this->fetchCurrentSet($client, (int) $id);

            if ($set !== null && $this->importSet($set, true)) {
                $changed[] = (int) $id;
                $this->line("set {$id}: now ".($set['status'] ?? $set['ranked'] ?? '?'));
            }

            usleep(static::CURRENT_SET_DELAY_US);
        }

        $this->line(count($ids).' qualified sets re-checked against osu.direct, '.count($changed).' changed');

        return $changed;
    }

    private function importSet(array $set, bool $current = false): bool
    {
        $id = (int) ($set['id'] ?? 0);
        $beatmaps = array_values(array_filter(
            $set['beatmaps'] ?? [],
            fn ($beatmap) => is_array($beatmap) && !($beatmap['convert'] ?? false),
        ));

        if ($id <= 0 || $beatmaps === []) {
            return false;
        }

        $fingerprint = md5(json_encode([
            $set['ranked'] ?? null,
            $set['last_updated'] ?? null,
            array_map(fn ($beatmap) => [$beatmap['id'], $beatmap['checksum'] ?? null, $beatmap['ranked'] ?? null], $beatmaps),
        ]));

        if (DB::table('delta_mirror_beatmapsets')->where('beatmapset_id', $id)->value('fingerprint') === $fingerprint) {
            return false;
        }

        $stored = DB::table('osu_beatmapsets')->where('beatmapset_id', $id)->first(['user_id', 'approved', 'last_update']);

        if ($stored !== null && (int) $stored->user_id !== 0) {
            return $this->skip($id, 'its id belongs to a set submitted on this server');
        }

        $approved = (int) ($set['ranked'] ?? 0);

        if (!$current && $stored !== null && $this->isOlderThanStored($set, $stored, $approved)) {
            $this->stale++;

            return false;
        }

        $beatmapIds = array_map(fn ($beatmap) => (int) $beatmap['id'], $beatmaps);
        $clash = DB::table('osu_beatmaps')
            ->whereIn('beatmap_id', $beatmapIds)
            ->where(fn ($query) => $query->whereNull('beatmapset_id')->orWhere('beatmapset_id', '<>', $id))
            ->exists();

        if ($clash) {
            return $this->skip($id, 'a difficulty id belongs to another set on this server');
        }

        $lastUpdate = static::time($set['last_updated'] ?? null) ?? now();
        $artist = (string) ($set['artist'] ?? '');
        $title = (string) ($set['title'] ?? '');
        $creator = (string) ($set['creator'] ?? 'unknown');

        $setRow = [
            'beatmapset_id' => $id,
            'user_id' => 0,
            'thread_id' => 0,
            'artist' => static::cut($artist, 80),
            'artist_unicode' => static::cut($set['artist_unicode'] ?? null, 80),
            'title' => static::cut($title, 80),
            'title_unicode' => static::cut($set['title_unicode'] ?? null, 80),
            'creator' => static::cut("Mirror ({$creator})", 80),
            'source' => static::cut((string) ($set['source'] ?? ''), 200),
            'tags' => static::cut((string) ($set['tags'] ?? ''), 1000),
            'video' => (int) (bool) ($set['video'] ?? false),
            'storyboard' => (int) (bool) ($set['storyboard'] ?? false),
            'bpm' => (float) ($set['bpm'] ?? 0),
            'versions_available' => min(count($beatmaps), 255),
            'approved' => $approved,
            'approved_date' => static::time($set['ranked_date'] ?? null),
            'submit_date' => static::time($set['submitted_date'] ?? null),
            'last_update' => $lastUpdate,
            'filename' => "{$id}.osz",
            'active' => 1,
            'rating' => 0,
            'offset' => (int) ($set['offset'] ?? 0),
            'displaytitle' => static::cut("[bold:0,size:20]{$artist}|{$title}", 200),
            'genre_id' => (int) ($set['genre_id'] ?? 1),
            'language_id' => (int) ($set['language_id'] ?? 1),
            'favourite_count' => 0,
            'play_count' => 0,
            'difficulty_names' => static::cut(implode(',', array_map(
                fn ($beatmap) => sprintf('%s ★%.2f@%d', $beatmap['version'] ?? '', (float) ($beatmap['difficulty_rating'] ?? 0), (int) ($beatmap['mode_int'] ?? 0)),
                $beatmaps,
            )), 2048),
            'cover_updated_at' => $lastUpdate,
            'discussion_enabled' => 0,
            'discussion_locked' => 1,
            'nsfw' => (int) (bool) ($set['nsfw'] ?? false),
            'anime_cover' => (int) (bool) ($set['anime_cover'] ?? false),
            'spotlight' => (int) (bool) ($set['spotlight'] ?? false),
            'track_id' => null,
            'deleted_at' => null,
        ];

        $beatmapRows = array_map(fn ($beatmap) => [
            'beatmap_id' => (int) $beatmap['id'],
            'beatmapset_id' => $id,
            'user_id' => 0,
            'checksum' => $beatmap['checksum'] ?? null,
            'version' => static::cut((string) ($beatmap['version'] ?? ''), 80),
            'total_length' => (int) ($beatmap['total_length'] ?? 0),
            'hit_length' => (int) ($beatmap['hit_length'] ?? 0),
            'countTotal' => (int) ($beatmap['count_circles'] ?? 0) + (int) ($beatmap['count_sliders'] ?? 0) + (int) ($beatmap['count_spinners'] ?? 0),
            'countNormal' => (int) ($beatmap['count_circles'] ?? 0),
            'countSlider' => (int) ($beatmap['count_sliders'] ?? 0),
            'countSpinner' => (int) ($beatmap['count_spinners'] ?? 0),
            'diff_drain' => (float) ($beatmap['drain'] ?? 0),
            'diff_size' => (float) ($beatmap['cs'] ?? 0),
            'diff_overall' => (float) ($beatmap['accuracy'] ?? 0),
            'diff_approach' => (float) ($beatmap['ar'] ?? 0),
            'playmode' => (int) ($beatmap['mode_int'] ?? 0),
            'approved' => (int) ($beatmap['ranked'] ?? $approved),
            'last_update' => static::time($beatmap['last_updated'] ?? null) ?? $lastUpdate,
            'difficultyrating' => (float) ($beatmap['difficulty_rating'] ?? 0),
            'max_combo' => (int) ($beatmap['max_combo'] ?? 0),
            'playcount' => 0,
            'passcount' => 0,
            'bpm' => (float) ($beatmap['bpm'] ?? $setRow['bpm']),
            'deleted_at' => null,
        ], $beatmaps);

        $storedOwners = json_decode((string) DB::table('delta_mirror_beatmapsets')->where('beatmapset_id', $id)->value('owners'), true) ?? [];

        $mirrorRow = [
            'beatmapset_id' => $id,
            'bancho_user_id' => (int) ($set['user_id'] ?? 0),
            'creator' => static::cut($creator, 80),
            'owners' => json_encode(array_combine(
                $beatmapIds,
                array_map(fn ($beatmap) => $beatmap['owners'] ?? $storedOwners[$beatmap['id']] ?? [], $beatmaps),
            )),
            'fingerprint' => $fingerprint,
            'synced_at' => now(),
        ];

        if ($this->dryRun) {
            return true;
        }

        DB::transaction(function () use ($beatmapIds, $beatmapRows, $id, $mirrorRow, $setRow) {
            DB::table('osu_beatmapsets')->upsert([$setRow], ['beatmapset_id'], array_values(array_diff(array_keys($setRow), static::LOCAL_SET_STATS)));
            DB::table('osu_beatmaps')->upsert($beatmapRows, ['beatmap_id'], array_values(array_diff(array_keys($beatmapRows[0]), static::LOCAL_BEATMAP_STATS)));
            DB::table('osu_beatmaps')
                ->where('beatmapset_id', $id)
                ->whereNotIn('beatmap_id', $beatmapIds)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);
            DB::table('delta_mirror_beatmapsets')->upsert([$mirrorRow], ['beatmapset_id'], array_keys($mirrorRow));
        });

        return true;
    }

    private function isOlderThanStored(array $set, object $stored, int $approved): bool
    {
        $incoming = static::time($set['last_updated'] ?? null);

        if ($incoming === null || $stored->last_update === null) {
            return false;
        }

        $storedUpdate = Carbon::parse($stored->last_update);

        if ($incoming->lt($storedUpdate)) {
            return true;
        }

        return $incoming->eq($storedUpdate)
            && $approved === Beatmapset::STATES['qualified']
            && in_array((int) $stored->approved, [Beatmapset::STATES['ranked'], Beatmapset::STATES['approved'], Beatmapset::STATES['loved']], true);
    }

    private function skip(int $id, string $reason): bool
    {
        $this->skipped++;
        $this->warn("skipped set {$id}: {$reason}");

        return false;
    }
}
