{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@extends('master', ['titlePrepend' => 'user management'])

@section('content')
    @include('admin._header', ['title' => 'users'])

    <div class="osu-page osu-page--admin delta-admin">
        @include('admin.delta._style')
        @include('admin.delta._controls_toggle')

        <form method="GET" action="{{ route('admin.delta.users') }}" class="delta-admin__card">
            <div class="delta-admin__card-title">find a user</div>
            <div class="delta-admin__row">
                <input type="text" name="q" value="{{ $query }}" placeholder="username or user id" size="30">
                <button type="submit">search</button>
            </div>
        </form>

        @if ($query !== null && $user === null)
            <div class="delta-admin__error">no user found for "{{ $query }}"</div>
        @endif

        @if ($user !== null)
            @php
                $userGroupIds = $user->groupIds();
            @endphp

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">{{ $user->username }}</div>

                <div class="delta-admin__facts">
                    <div>
                        <div class="delta-admin__fact-label">user id</div>
                        <div class="delta-admin__fact-value">{{ $user->getKey() }}</div>
                    </div>
                    <div>
                        <div class="delta-admin__fact-label">restricted</div>
                        <div class="delta-admin__fact-value">{{ $user->isRestricted() ? 'yes' : 'no' }}</div>
                    </div>
                    <div>
                        <div class="delta-admin__fact-label">silenced</div>
                        <div class="delta-admin__fact-value">{{ $user->isSilenced() ? 'yes' : 'no' }}</div>
                    </div>
                    <div>
                        <div class="delta-admin__fact-label">joined</div>
                        <div class="delta-admin__fact-value">{{ $user->user_regdate?->format('Y-m-d') ?? 'unknown' }}</div>
                    </div>
                </div>

                <div class="delta-admin__fact-label">groups</div>
                <div style="margin-top: 6px;">
                    @forelse ($groups->whereIn('group_id', $userGroupIds) as $g)
                        <span class="delta-admin__tag">{{ $g->group_name }}</span>
                    @empty
                        <span style="opacity: 0.6;">none</span>
                    @endforelse
                </div>
            </div>

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">groups</div>

                <form method="POST" action="{{ route('admin.delta.users.action', $user->getKey()) }}">
                    @csrf
                    <div class="delta-admin__row">
                        <select name="group_id">
                            @foreach ($groups as $g)
                                <option value="{{ $g->group_id }}">{{ $g->group_name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" name="action" value="group_add">add</button>
                        <button type="submit" name="action" value="group_remove" class="is-quiet">remove</button>
                    </div>
                </form>
            </div>

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">profile title</div>

                <div class="delta-admin__row">
                    <span class="delta-admin__tag">{{ $user->title() ?? 'none' }}</span>
                </div>

                <form method="POST" action="{{ route('admin.delta.users.action', $user->getKey()) }}">
                    @csrf
                    <div class="delta-admin__row">
                        <select name="rank_id">
                            @foreach ($ranks as $rank)
                                <option value="{{ $rank->getKey() }}">{{ $rank->rank_title }}</option>
                            @endforeach
                        </select>
                        <button type="submit" name="action" value="title_set">apply</button>
                        <button type="submit" name="action" value="title_clear" class="is-quiet">clear</button>
                    </div>
                    <div class="delta-admin__row">
                        <input type="text" name="rank_title" placeholder="rename (optional)" size="26">
                        <input type="text" name="url" placeholder="link" size="34">
                        <button type="submit" name="action" value="title_update" class="is-quiet">update selected</button>
                        <button type="submit" name="action" value="title_delete" class="is-quiet">delete selected</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.delta.users.action', $user->getKey()) }}">
                    @csrf
                    <div class="delta-admin__row">
                        <input type="text" name="rank_title" placeholder="new title" size="26">
                        <input type="text" name="url" placeholder="link (optional)" size="34">
                        <button type="submit" name="action" value="title_create">create &amp; apply</button>
                    </div>
                </form>
            </div>

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">badges</div>

                @forelse ($badges as $badge)
                    <form method="POST" action="{{ route('admin.delta.users.action', $user->getKey()) }}" class="delta-admin__row">
                        @csrf
                        <img src="{{ $badge->imageUrl() }}" alt="{{ $badge->description }}" style="height: 32px;">
                        <span>{{ $badge->description }}</span>
                        <input type="hidden" name="image" value="{{ $badge->image }}">
                        <button type="submit" name="action" value="badge_remove" class="is-quiet">remove</button>
                    </form>
                @empty
                    <div style="opacity: 0.6;">none</div>
                @endforelse

                <form method="POST" action="{{ route('admin.delta.users.action', $user->getKey()) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="delta-admin__row">
                        <input type="text" name="description" placeholder="description" size="30">
                        <input type="text" name="url" placeholder="link (optional)" size="34">
                    </div>
                    <div class="delta-admin__row">
                        <input type="file" name="image_file" accept="image/*">
                        <button type="submit" name="action" value="badge_add">add</button>
                    </div>
                    <div class="delta-admin__row">
                        <input type="text" name="image" placeholder="or an image url" size="52">
                    </div>
                    <div style="opacity: 0.6; font-size: 13px;">
                        uploads are stored on this server, under 1MB. a url is only used when no file is picked
                    </div>
                </form>
            </div>

            <div class="delta-admin__card">
                <div class="delta-admin__card-title">moderation</div>

                <form method="POST" action="{{ route('admin.delta.users.action', $user->getKey()) }}">
                    @csrf
                    <div class="delta-admin__row">
                        <input type="text" name="reason" placeholder="reason" size="44">
                    </div>
                    <div class="delta-admin__row">
                        <button type="submit" name="action" value="note" class="is-quiet">add note</button>
                        <input type="number" name="hours" value="24" min="1" style="width: 90px;">
                        <button type="submit" name="action" value="silence">silence (hours)</button>
                    </div>
                    <div class="delta-admin__row">
                        @if ($user->isRestricted())
                            <button type="submit" name="action" value="unrestrict" class="is-quiet">lift restriction</button>
                        @else
                            <button type="submit" name="action" value="restrict">restrict account</button>
                        @endif
                    </div>
                </form>
            </div>
        @endif
        @include('admin.delta._original_notice')
    </div>
@endsection
