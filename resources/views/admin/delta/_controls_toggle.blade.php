{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@php
    $enabled = App\Libraries\DeltaModeration::controlsEnabled();
@endphp
<form method="POST" action="{{ route('delta-moderation.toggle') }}" class="delta-admin__card">
    @csrf
    <div class="delta-admin__card-title">site admin controls</div>
    <div class="delta-admin__row">
        <div>
            {{ $enabled
                ? 'on - the tools button appears on beatmap and profile pages'
                : 'off - beatmap and profile pages show nothing extra' }}
        </div>
        <button type="submit">{{ $enabled ? 'turn off' : 'turn on' }}</button>
    </div>
</form>
