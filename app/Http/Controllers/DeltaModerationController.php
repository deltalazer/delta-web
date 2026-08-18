<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Http\Controllers;

use App\Libraries\DeltaModeration;
use App\Models\Beatmapset;
use App\Models\User;
use Auth;
use Request;

class DeltaModerationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->middleware(function ($request, $next) {
            $user = Auth::user();

            if (!$user->isAdmin() && !$user->isModerator()) {
                abort(403);
            }

            return $next($request);
        });

        parent::__construct();
    }

    public function beatmapsetAction($id)
    {
        $beatmapset = Beatmapset::findOrFail($id);

        return $this->apply(fn () => DeltaModeration::applyBeatmapsetAction(
            $beatmapset,
            Auth::user(),
            Request::input('action') ?? '',
            Request::all(),
        ));
    }

    public function toggle()
    {
        session()->put(DeltaModeration::SESSION_KEY, !DeltaModeration::controlsEnabled());

        return back();
    }

    public function userAction($id)
    {
        $user = User::findOrFail($id);

        return $this->apply(fn () => DeltaModeration::applyUserAction(
            $user,
            Auth::user(),
            Request::input('action') ?? '',
            Request::all(),
        ));
    }

    private function apply(callable $action)
    {
        try {
            $message = $action();
        } catch (\Exception $e) {
            return back()->with('delta_error', $e->getMessage());
        }

        return back()->with('delta_notice', $message);
    }
}
