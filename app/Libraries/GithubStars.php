<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Libraries;

use App\Models\GithubUser;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;

class GithubStars
{
    const API_VERSION = '2022-11-28';
    const PER_PAGE = 100;

    public static function isConfigured(): bool
    {
        return static::repository() !== null;
    }

    public static function repository(): ?string
    {
        return $GLOBALS['cfg']['osu']['github']['supporter_repository'];
    }

    public static function applyToUser(User $user, ?Carbon $starredAt): void
    {
        $isSupporter = $starredAt !== null;
        $updates = ['osu_subscriber' => $isSupporter];

        if ($isSupporter) {
            $updates['osu_subscriptionexpiry'] = Carbon::now()->addYear();
        }

        User::whereKey($user->getKey())->update($updates);
    }

    public static function starredAt(string $accessToken): ?Carbon
    {
        $response = (new Client())->get(static::url('user/starred/'.static::repository()), [
            'headers' => static::headers('application/vnd.github+json', $accessToken),
            'http_errors' => false,
        ]);

        return $response->getStatusCode() === 204 ? Carbon::now() : null;
    }

    public static function syncAll(): int
    {
        $stargazers = static::fetchStargazers();

        static::updateStarredAt($stargazers);
        static::reconcileSupporters();

        return count($stargazers);
    }

    private static function fetchStargazers(): array
    {
        $client = new Client();
        $stargazers = [];
        $page = 1;

        while (true) {
            $response = $client->get(static::url('repos/'.static::repository().'/stargazers'), [
                'headers' => static::headers(
                    'application/vnd.github.star+json',
                    $GLOBALS['cfg']['github']['connections']['main']['token'],
                ),
                'query' => ['page' => $page, 'per_page' => static::PER_PAGE],
            ]);

            $entries = json_decode($response->getBody()->getContents(), true);

            foreach ($entries as $entry) {
                $stargazers[$entry['user']['id']] = Carbon::parse($entry['starred_at']);
            }

            if (count($entries) < static::PER_PAGE) {
                return $stargazers;
            }

            $page++;
        }
    }

    private static function headers(string $accept, ?string $token): array
    {
        $headers = [
            'Accept' => $accept,
            'X-GitHub-Api-Version' => static::API_VERSION,
        ];

        if (present($token)) {
            $headers['Authorization'] = "Bearer {$token}";
        }

        return $headers;
    }

    private static function reconcileSupporters(): void
    {
        $supporterIds = GithubUser::whereNotNull('user_id')
            ->whereNotNull('starred_at')
            ->pluck('user_id')
            ->all();

        User::where('osu_subscriber', true)
            ->whereNotIn('user_id', $supporterIds)
            ->update(['osu_subscriber' => false]);

        User::where('osu_subscriber', false)
            ->whereIn('user_id', $supporterIds)
            ->update(['osu_subscriber' => true]);

        User::whereIn('user_id', $supporterIds)
            ->where(fn ($q) => $q->whereNull('osu_subscriptionexpiry')->orWhere('osu_subscriptionexpiry', '<', Carbon::now()))
            ->update(['osu_subscriptionexpiry' => Carbon::now()->addYear()]);
    }

    private static function updateStarredAt(array $stargazers): void
    {
        $canonicalIds = array_keys($stargazers);

        GithubUser::whereNotNull('starred_at')
            ->whereNotIn('canonical_id', $canonicalIds)
            ->update(['starred_at' => null]);

        GithubUser::whereNull('starred_at')
            ->whereIn('canonical_id', $canonicalIds)
            ->get()
            ->each(function (GithubUser $githubUser) use ($stargazers) {
                $githubUser->starred_at = $stargazers[$githubUser->canonical_id];
                $githubUser->saveOrExplode();
            });
    }

    private static function url(string $path): string
    {
        return "https://api.github.com/{$path}";
    }
}
