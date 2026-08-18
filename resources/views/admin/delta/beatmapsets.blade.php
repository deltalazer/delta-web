{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@extends('master', ['titlePrepend' => 'beatmap management'])

@section('content')
    @include('admin._header', ['title' => 'beatmaps'])

    <div class="osu-page osu-page--admin delta-admin">
        @include('admin.delta._style')
        @include('admin.delta._controls_toggle')

        <form method="GET" action="{{ route('admin.delta.beatmapsets') }}" class="delta-admin__card">
            <div class="delta-admin__card-title">find a beatmapset</div>
            <div class="delta-admin__row">
                <input type="text" name="q" value="{{ $query }}" placeholder="beatmapset id" size="20">
                <button type="submit">search</button>
            </div>
            <div style="opacity: 0.6; font-size: 13px;">
                the number in the url: {{ $GLOBALS['cfg']['app']['url'] }}/beatmapsets/<strong>12345</strong>
            </div>
        </form>

        @if ($query !== null && $beatmapset === null)
            <div class="delta-admin__error">no beatmapset found with id {{ $query }}</div>
        @endif

        @if ($beatmapset !== null)
            @php
                $state = array_search($beatmapset->approved, App\Models\Beatmapset::STATES, true) ?: 'unknown';
            @endphp

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">{{ $beatmapset->artist }} - {{ $beatmapset->title }}</div>

                <div class="delta-admin__facts">
                    <div>
                        <div class="delta-admin__fact-label">set id</div>
                        <div class="delta-admin__fact-value">{{ $beatmapset->getKey() }}</div>
                    </div>
                    <div>
                        <div class="delta-admin__fact-label">status</div>
                        <div class="delta-admin__fact-value">{{ $state }}</div>
                    </div>
                    <div>
                        <div class="delta-admin__fact-label">creator</div>
                        <div class="delta-admin__fact-value">{{ $beatmapset->creator }}</div>
                    </div>
                    <div>
                        <div class="delta-admin__fact-label">difficulties</div>
                        <div class="delta-admin__fact-value">{{ $beatmapset->beatmaps()->count() }}</div>
                    </div>
                </div>

                <a href="{{ route('beatmapsets.show', $beatmapset->getKey()) }}">view on the site</a>
            </div>

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">change status</div>

                <form method="POST" action="{{ route('admin.delta.beatmapsets.action', $beatmapset->getKey()) }}">
                    @csrf
                    <div class="delta-admin__row">
                        <button type="submit" name="action" value="rank">rank</button>
                        <button type="submit" name="action" value="qualify">qualify</button>
                        <button type="submit" name="action" value="love">love</button>
                        <button type="submit" name="action" value="pending" class="is-quiet">pending</button>
                        <button type="submit" name="action" value="graveyard" class="is-quiet">graveyard</button>
                    </div>
                </form>

            </div>

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">unrank</div>

                <form method="POST" action="{{ route('admin.delta.beatmapsets.action', $beatmapset->getKey()) }}">
                    @csrf
                    <div class="delta-admin__row">
                        <input type="text" name="reason" placeholder="reason (posted publicly)" size="50" required>
                        <button type="submit" name="action" value="unrank">unrank</button>
                    </div>
                </form>

                <div style="opacity: 0.6; font-size: 13px; margin-top: 10px;">
                    posts the reason as a discussion problem, resets nominations and returns the set to
                    pending. scores are kept - they simply stop counting while the set is unranked.
                </div>
            </div>
        @endif
    </div>
@endsection
