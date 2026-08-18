{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@extends('master', ['titlePrepend' => 'forum management'])

@section('content')
    @include('admin._header', ['title' => 'forums'])

    <div class="osu-page osu-page--admin delta-admin">
        @include('admin.delta._style')
        @include('admin.delta._controls_toggle')

        @php
            $categories = $forums->where('parent_id', 0);
        @endphp

        <div class="delta-admin__card">
            <div class="delta-admin__card-title">add</div>

            <form method="POST" action="{{ route('admin.delta.forums.create') }}">
                @csrf
                <div class="delta-admin__row">
                    <input type="text" name="name" placeholder="name" size="30" required>
                    <input type="text" name="description" placeholder="description" size="40">
                </div>
                <div class="delta-admin__row">
                    <select name="parent_id">
                        <option value="0">(top level category)</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->getKey() }}">{{ $category->forum_name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" name="action" value="create">create</button>
                </div>
                <div style="opacity: 0.6; font-size: 13px;">
                    picking a category creates a forum inside it, otherwise a new top level category is made
                </div>
            </form>
        </div>

        @foreach ($categories as $category)
            <div class="delta-admin__card">
                <div class="delta-admin__card-title">{{ $category->forum_name }}</div>

                @include('admin.delta._forum_row', ['forum' => $category])

                @foreach ($forums->where('parent_id', $category->getKey()) as $forum)
                    <div style="margin-left: 24px;">
                        @include('admin.delta._forum_row', ['forum' => $forum])
                    </div>
                @endforeach
            </div>
        @endforeach

        @php
            $orphans = $forums->filter(fn ($forum) => $forum->parent_id !== 0 && $categories->where('forum_id', $forum->parent_id)->isEmpty());
        @endphp

        @if ($orphans->isNotEmpty())
            <div class="delta-admin__card">
                <div class="delta-admin__card-title">orphaned</div>
                <div style="opacity: 0.6; font-size: 13px; margin-bottom: 8px;">
                    the parent of these no longer exists
                </div>
                @foreach ($orphans as $forum)
                    @include('admin.delta._forum_row', ['forum' => $forum])
                @endforeach
            </div>
        @endif
    </div>
@endsection
