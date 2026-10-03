<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Libraries;

use App\Models\GithubUser;
use App\Models\Repository;
use App\Models\UpdateStream;
use Carbon\Carbon;
use GuzzleHttp\Client;

class ChangelogReleases
{
    const API_VERSION = '2022-11-28';
    const CATEGORY = 'Changes';
    const PER_PAGE = 100;

    public static function isConfigured(): bool
    {
        return static::repository() !== null;
    }

    public static function repository(): ?string
    {
        return $GLOBALS['cfg']['osu']['changelog']['release_repository'];
    }

    public static function syncAll(): int
    {
        $releases = static::fetchReleases();
        $repository = Repository::firstOrCreate(['name' => static::repository()]);
        $stream = static::updateStream();

        foreach (array_reverse($releases) as $release) {
            static::importRelease($release, $stream, $repository);
        }

        return count($releases);
    }

    private static function fetchReleases(): array
    {
        $headers = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => static::API_VERSION,
        ];

        $token = $GLOBALS['cfg']['github']['connections']['main']['token'];
        if (present($token)) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        $response = (new Client())->get(static::url('repos/'.static::repository().'/releases'), [
            'headers' => $headers,
            'query' => ['per_page' => static::PER_PAGE],
        ]);

        $releases = json_decode($response->getBody()->getContents(), true);

        return array_values(array_filter($releases, fn ($release) => !$release['draft']));
    }

    private static function importRelease(array $release, UpdateStream $stream, Repository $repository): void
    {
        $publishedAt = Carbon::parse($release['published_at']);

        $build = $stream->builds()->updateOrCreate(
            ['version' => static::version($release['tag_name'])],
            ['allow_bancho' => true, 'date' => $publishedAt],
        );

        $entry = $repository->changelogEntries()->firstOrNew(['url' => $release['html_url']]);
        $entry->fill([
            'category' => static::CATEGORY,
            'created_at' => $publishedAt,
            'major' => true,
            'message' => presence($release['body']),
            'private' => false,
            'title' => presence($release['name']) ?? $release['tag_name'],
            'type' => 'misc',
        ]);

        if ($release['author'] !== null) {
            $entry->githubUser()->associate(GithubUser::importFromGithub($release['author']));
        }

        $entry->saveOrExplode();

        $build->changelogEntries()->syncWithoutDetaching([$entry->getKey()]);

        $manualEntries = $build->changelogEntries()->whereNull('changelog_entries.repository_id')->get();

        foreach ($manualEntries as $manualEntry) {
            $build->changelogEntries()->detach($manualEntry);
            $manualEntry->delete();
        }
    }

    private static function updateStream(): UpdateStream
    {
        return UpdateStream::updateOrCreate(
            ['stream_id' => $GLOBALS['cfg']['osu']['changelog']['featured_stream']],
            ['name' => 'delta', 'pretty_name' => 'deltalazer'],
        );
    }

    private static function url(string $path): string
    {
        return "https://api.github.com/{$path}";
    }

    private static function version(string $tagName): string
    {
        return ltrim($tagName, 'v');
    }
}
