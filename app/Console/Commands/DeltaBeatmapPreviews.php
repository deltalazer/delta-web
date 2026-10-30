<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Beatmapset;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use ZipArchive;

class DeltaBeatmapPreviews extends Command
{
    const LENGTH_SECONDS = 10;

    protected $signature = 'delta:beatmap-previews
        {--set=* : Only these beatmapset ids}
        {--force : Remake previews that already exist}';

    protected $description = 'Make the 10 second audio previews for beatmapsets uploaded to this server.';

    public function handle(): int
    {
        $ids = array_map('intval', $this->option('set'));
        $sets = Beatmapset::where('user_id', '<>', 0)
            ->when($ids !== [], fn ($query) => $query->whereIn('beatmapset_id', $ids))
            ->orderBy('beatmapset_id')
            ->pluck('beatmapset_id');
        $made = 0;
        $failed = 0;

        foreach ($sets as $id) {
            $osz = rtrim($GLOBALS['cfg']['osu']['beatmap_submission']['storage_path'], '/').'/'.$id;
            $target = Beatmapset::localPreviewPath((int) $id);

            if (!is_file($osz)) {
                continue;
            }

            if (!$this->option('force') && is_file($target) && filemtime($target) >= filemtime($osz)) {
                continue;
            }

            $error = $this->generate($osz, $target);

            if ($error === null) {
                $made++;
                $this->line("set {$id}: preview made");
            } else {
                $failed++;
                $this->warn("set {$id}: {$error}");
            }
        }

        $this->info("{$made} previews made, {$failed} failed");

        return 0;
    }

    private function generate(string $osz, string $target): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($osz) !== true) {
            return 'the package could not be opened';
        }

        $audioName = null;
        $previewTime = -1;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'osu') {
                continue;
            }

            $osu = (string) $zip->getFromIndex($i);

            if (preg_match('/^AudioFilename:\s*(.+?)\s*$/m', $osu, $match) === 1) {
                $audioName = $match[1];
                $previewTime = preg_match('/^PreviewTime:\s*(-?\d+)/m', $osu, $time) === 1 ? (int) $time[1] : -1;
                break;
            }
        }

        $audioIndex = null;

        for ($i = 0; $audioName !== null && $i < $zip->numFiles; $i++) {
            if (strcasecmp($zip->getNameIndex($i), $audioName) === 0) {
                $audioIndex = $i;
                break;
            }
        }

        if ($audioIndex === null) {
            $zip->close();

            return 'no audio file found in the package';
        }

        $audio = tempnam(sys_get_temp_dir(), 'preview-audio-');
        file_put_contents($audio, $zip->getFromIndex($audioIndex));
        $zip->close();

        try {
            $start = $previewTime >= 0 ? $previewTime / 1000 : $this->duration($audio) * 0.4;
            $output = tempnam(sys_get_temp_dir(), 'preview-mp3-').'.mp3';
            $process = new Process([
                'ffmpeg', '-y', '-loglevel', 'error',
                '-ss', sprintf('%.3f', $start),
                '-t', (string) static::LENGTH_SECONDS,
                '-i', $audio,
                '-vn', '-ac', '2', '-ar', '44100', '-b:a', '64k',
                '-af', 'afade=t=in:d=0.5,afade=t=out:st='.(static::LENGTH_SECONDS - 1).':d=1',
                $output,
            ]);
            $process->setTimeout(60);
            $process->run();

            if (!$process->isSuccessful() || !is_file($output) || filesize($output) === 0) {
                return 'ffmpeg failed: '.trim($process->getErrorOutput());
            }

            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            if (!rename($output, $target)) {
                return 'the preview could not be saved';
            }

            return null;
        } finally {
            @unlink($audio);
            if (isset($output) && is_file($output)) {
                @unlink($output);
            }
        }
    }

    private function duration(string $audio): float
    {
        $process = new Process(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', $audio]);
        $process->setTimeout(30);
        $process->run();

        return max(0.0, (float) trim($process->getOutput()));
    }
}
