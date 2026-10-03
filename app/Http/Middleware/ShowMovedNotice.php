<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ShowMovedNotice
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->query('from') === 'vercel') {
            $request->session()->flash('delta_moved_notice', true);
            $query = array_diff_key($request->query(), ['from' => null]);

            return redirect(url($request->path()).($query === [] ? '' : '?'.http_build_query($query)), 301);
        }

        return $next($request);
    }
}
