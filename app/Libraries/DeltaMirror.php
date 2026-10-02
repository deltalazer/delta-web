<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Libraries;

use App\Models\Beatmap;
use App\Models\Beatmapset;
use Cache;
use GuzzleHttp\Client;
use Throwable;

class DeltaMirror
{
    const HEALTH_CACHE_KEY = 'delta_mirror_health';
    const HEALTH_TTL = 300;
    const USER_AGENT = 'delta!lazer (+https://delta.mikuuu.xyz)';

    const MIRRORS = [
        'catboy' => [
            'download' => 'https://catboy.best/d/%d',
            'download_novideo' => 'https://catboy.best/d/%dn',
            'health' => 'https://catboy.best/api/v2/s/39804',
        ],
        'osu.direct' => [
            'download' => 'https://osu.direct/api/d/%d',
            'download_novideo' => 'https://osu.direct/api/d/%d?noVideo=1',
            'health' => 'https://osu.direct/api/v2/s/39804',
        ],
    ];

    const OSU_FILE_SOURCES = [
        'https://osu.ppy.sh/osu/%d',
        'https://osu.direct/api/osu/%d',
    ];

    public static function isMirrored(Beatmapset $beatmapset): bool
    {
        return (int) $beatmapset->user_id === 0;
    }

    public static function available(): array
    {
        $health = Cache::get(static::HEALTH_CACHE_KEY);

        return array_values(array_filter(
            array_keys(static::MIRRORS),
            fn (string $name) => $health === null || ($health[$name] ?? false),
        ));
    }

    public static function anyAvailable(): bool
    {
        return static::available() !== [];
    }

    public static function downloadUrl(Beatmapset $beatmapset, bool $noVideo = false): ?string
    {
        $name = static::available()[0] ?? null;

        return $name === null ? null : sprintf(static::MIRRORS[$name][$noVideo ? 'download_novideo' : 'download'], $beatmapset->getKey());
    }

    public static function checkHealth(): array
    {
        $client = static::client();
        $health = [];

        foreach (static::MIRRORS as $name => $mirror) {
            try {
                $health[$name] = $client->get($mirror['health'])->getStatusCode() === 200;
            } catch (Throwable) {
                $health[$name] = false;
            }
        }

        Cache::put(static::HEALTH_CACHE_KEY, $health, static::HEALTH_TTL);

        return $health;
    }

    public static function osuFile(Beatmap $beatmap): ?string
    {
        $path = storage_path("app/delta-mirror-osu/{$beatmap->getKey()}.osu");

        if (is_file($path) && ($beatmap->checksum === null || md5_file($path) === $beatmap->checksum)) {
            return $path;
        }

        $client = static::client();

        foreach (static::OSU_FILE_SOURCES as $source) {
            try {
                $body = (string) $client->get(sprintf($source, $beatmap->getKey()))->getBody();
            } catch (Throwable) {
                continue;
            }

            if (!str_starts_with(ltrim($body, "\u{FEFF} \t\r\n"), 'osu file format')) {
                continue;
            }

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            file_put_contents($path, $body);

            return $path;
        }

        return null;
    }

    public static function client(): Client
    {
        return new Client([
            'headers' => ['User-Agent' => static::USER_AGENT],
            'timeout' => 30,
        ]);
    }
}
