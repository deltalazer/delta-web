{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
<form method="POST" action="{{ route('admin.delta.forums.action', $forum->getKey()) }}" class="delta-admin__row">
    @csrf
    <span class="delta-admin__tag">{{ $forum->getKey() }}</span>
    <input type="text" name="name" value="{{ $forum->forum_name }}" size="26">
    <input type="text" name="description" value="{{ $forum->forum_desc }}" size="34">
    <button type="submit" name="action" value="rename">save</button>
    <button type="submit" name="action" value="move-up" class="is-quiet">up</button>
    <button type="submit" name="action" value="move-down" class="is-quiet">down</button>
    <button type="submit" name="action" value="delete" class="is-quiet">delete</button>
    <span style="opacity: 0.6; font-size: 13px;">{{ $forum->topics()->count() }} topics</span>
</form>
