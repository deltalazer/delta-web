<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Http\Controllers;

use App\Libraries\ImageProcessor;
use App\Libraries\StorageUrl;
use Storage;

class ImageProcessorController extends Controller
{
    const MAX_FILE_SIZE = 2000000;

    const SIZES = [
        'card' => [400, 140],
        'card@2x' => [800, 280],
        'cover' => [900, 250],
        'cover@2x' => [1800, 500],
        'list' => [300, 80],
        'list@2x' => [600, 160],
        'slimcover' => [1920, 360],
        'slimcover@2x' => [3840, 720],
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function optimize($path)
    {
        return $this->respond($path, null);
    }

    public function thumbnail($size, $path)
    {
        abort_unless(array_key_exists($size, static::SIZES), 404);

        return $this->respond($path, static::SIZES[$size]);
    }

    private function respond(string $path, ?array $dimensions)
    {
        $base = preg_replace('#^https?://#', '', StorageUrl::make(null, ''));

        abort_unless(str_starts_with($path, $base), 404);

        $relative = substr($path, strlen($base));

        abort_unless(Storage::exists($relative), 404);

        $file = tmpfile();
        fwrite($file, Storage::get($relative));
        $filename = get_stream_filename($file);

        if ($dimensions !== null) {
            (new ImageProcessor($filename, $dimensions, static::MAX_FILE_SIZE))->process();
        }

        return response(file_get_contents($filename), 200, ['Content-Type' => 'image/jpeg']);
    }
}
