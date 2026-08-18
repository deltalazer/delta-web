{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
<form method="POST" action="{{ $route }}">
    @csrf
    <input type="hidden" name="action" value="{{ $action }}">

    <button class="admin-menu-item" type="submit">
        <span class="admin-menu-item__content">
            <span class="admin-menu-item__label admin-menu-item__label--icon">
                <span class="{{ $icon }}"></span>
            </span>

            <span class="admin-menu-item__label admin-menu-item__label--text">
                {{ str_replace('_', ' ', $action) }}
            </span>
        </span>
    </button>
</form>
