{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@php
    $wikiPath = (string) request()->route('path');
    $copyNotice = match (true) {
        request()->routeIs('legal') => 'legal',
        $wikiPath === 'Delta' || str_starts_with($wikiPath, 'Delta/') => null,
        request()->routeIs('wiki.show', 'wiki.sitemap') => 'wiki',
        default => null,
    };
@endphp

@if ($copyNotice !== null)
    @include('objects._notification_banner', [
        'type' => $copyNotice === 'legal' ? 'alert' : 'warning',
        'title' => osu_trans("layout.copy_notice.{$copyNotice}.title"),
        'message' => osu_trans("layout.copy_notice.{$copyNotice}.message", [
            'link' => link_to(
                'https://osu.ppy.sh'.request()->getPathInfo(),
                osu_trans('layout.copy_notice.original'),
                ['rel' => 'nofollow noopener', 'target' => '_blank'],
            ),
        ]),
    ])
@endif
