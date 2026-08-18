<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Http\Controllers\Admin;

use App\Libraries\DeltaForums;
use App\Libraries\DeltaModeration;
use App\Models\Beatmapset;
use App\Models\Forum\Forum;
use App\Models\Group;
use App\Models\Rank;
use App\Models\User;
use Auth;
use Request;

class DeltaController extends Controller
{
    public function users()
    {
        $query = presence(Request::input('q'));
        $user = $query === null ? null : User::lookup($query);

        return ext_view('admin.delta.users', [
            'badges' => $user === null ? [] : $user->badges()->get(),
            'groups' => Group::orderBy('group_id')->get(),
            'query' => $query,
            'ranks' => Rank::orderBy('rank_title')->get(),
            'user' => $user,
        ]);
    }

    public function userAction($id)
    {
        $user = User::findOrFail($id);
        $back = redirect(route('admin.delta.users', ['q' => $user->username]));

        try {
            $message = DeltaModeration::applyUserAction(
                $user,
                Auth::user(),
                Request::input('action') ?? '',
                Request::all(),
            );
        } catch (\Exception $e) {
            return $back->with('delta_error', $e->getMessage());
        }

        return $back->with('delta_notice', "{$user->username}: {$message}");
    }

    public function forums()
    {
        return ext_view('admin.delta.forums', [
            'forums' => Forum::orderBy('left_id')->orderBy('forum_id')->get(),
        ]);
    }

    public function forumAction($id = null)
    {
        $forum = $id === null ? null : Forum::findOrFail($id);
        $back = redirect(route('admin.delta.forums'));

        try {
            $message = DeltaForums::apply(
                $forum,
                Request::input('action') ?? '',
                Request::all(),
            );
        } catch (\Exception $e) {
            return $back->with('delta_error', $e->getMessage());
        }

        return $back->with('delta_notice', $message);
    }

    public function beatmapsets()
    {
        $query = get_int(Request::input('q'));
        $beatmapset = $query === null ? null : Beatmapset::find($query);

        return ext_view('admin.delta.beatmapsets', [
            'beatmapset' => $beatmapset,
            'query' => $query,
        ]);
    }

    public function beatmapsetAction($id)
    {
        $beatmapset = Beatmapset::findOrFail($id);
        $back = redirect(route('admin.delta.beatmapsets', ['q' => $beatmapset->getKey()]));

        try {
            $message = DeltaModeration::applyBeatmapsetAction(
                $beatmapset,
                Auth::user(),
                Request::input('action') ?? '',
                Request::all(),
            );
        } catch (\Exception $e) {
            return $back->with('delta_error', $e->getMessage());
        }

        return $back->with('delta_notice', "{$beatmapset->title}: {$message}");
    }
}
