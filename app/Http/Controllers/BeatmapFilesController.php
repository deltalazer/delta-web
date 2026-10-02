<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Http\Controllers;

use App\Libraries\DeltaMirror;
use App\Models\Beatmap;
use App\Models\Beatmapset;
use DB;

class BeatmapFilesController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function package($id)
    {
        $beatmapset = Beatmapset::findOrFail($id);

        if (DeltaMirror::isMirrored($beatmapset)) {
            return redirect(DeltaMirror::downloadUrl($beatmapset) ?? abort(503));
        }

        $path = $GLOBALS['cfg']['osu']['beatmap_submission']['storage_path'].'/'.$beatmapset->getKey();

        abort_unless(is_readable($path), 404);

        return response()->file($path, ['Content-Type' => 'application/x-osu-beatmap-archive']);
    }

    public function show($id)
    {
        $beatmap = Beatmap::with('beatmapset')->findOrFail($id);

        if ($beatmap->beatmapset !== null && DeltaMirror::isMirrored($beatmap->beatmapset)) {
            $path = DeltaMirror::osuFile($beatmap);

            abort_if($path === null, 404);

            return response()->file($path, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        abort_if($beatmap->filename === null, 404);

        $sha2 = DB::table('beatmapset_versions', 'v')
            ->join('beatmapset_version_files as vf', 'vf.version_id', '=', 'v.version_id')
            ->join('beatmapset_files as f', 'f.file_id', '=', 'vf.file_id')
            ->where('v.beatmapset_id', $beatmap->beatmapset_id)
            ->where('vf.filename', $beatmap->filename)
            ->orderByDesc('v.version_id')
            ->value('f.sha2_hash');

        abort_if($sha2 === null, 404);

        $path = $GLOBALS['cfg']['osu']['beatmap_submission']['storage_path'].'/files/'.bin2hex($sha2);

        abort_unless(is_readable($path), 404);

        return response()->file($path, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}
