{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@php
    $route = route('delta-moderation.users', $user->getKey());
@endphp
<div class="admin-menu">
    <button class="admin-menu__button js-menu" data-menu-target="admin-delta-user" type="button">
        <span class="fas fa-angle-up"></span>
        <span class="admin-menu__button-icon fas fa-tools"></span>
    </button>

    <div class="admin-menu__menu js-menu" data-menu-id="admin-delta-user" data-visibility="hidden">
        @include('delta._admin_menu_flash')

        @foreach ($user->isRestricted() ? ['unrestrict' => 'fas fa-unlock'] : ['restrict' => 'fas fa-lock'] as $action => $icon)
            @include('delta._admin_menu_action', compact('action', 'icon', 'route'))
        @endforeach

        <form method="POST" action="{{ $route }}">
            @csrf
            <input type="hidden" name="action" value="silence">

            <div class="admin-menu-item__content">
                <span class="admin-menu-item__label admin-menu-item__label--icon">
                    <span class="fas fa-comment-slash"></span>
                </span>

                <span class="admin-menu-item__label admin-menu-item__label--text">
                    <input name="hours" type="number" min="1" value="1" size="3" placeholder="hours">
                    <input name="reason" type="text" size="18" placeholder="reason">
                    <button type="submit">silence</button>
                </span>
            </div>
        </form>

        @if ($currentUser->isSuperAdmin())
            @include('delta._admin_menu_action', [
                'action' => 'avatar_remove',
                'icon' => 'fas fa-user-slash',
                'route' => $route,
            ])

            <form method="POST" action="{{ $route }}">
                @csrf
                <input type="hidden" name="action" value="username">

                <div class="admin-menu-item__content">
                    <span class="admin-menu-item__label admin-menu-item__label--icon">
                        <span class="fas fa-signature"></span>
                    </span>

                    <span class="admin-menu-item__label admin-menu-item__label--text">
                        <input name="username" type="text" size="18" placeholder="new username">
                        <button type="submit">rename</button>
                    </span>
                </div>
            </form>
        @endif

        @if ($currentUser->isAdmin())
            <a class="admin-menu-item" href="{{ route('admin.delta.users', ['q' => $user->username]) }}" target="_blank">
                <span class="admin-menu-item__content">
                    <span class="admin-menu-item__label admin-menu-item__label--icon">
                        <span class="fas fa-cogs"></span>
                    </span>

                    <span class="admin-menu-item__label admin-menu-item__label--text">
                        user management
                    </span>
                </span>
            </a>
        @endif
    </div>
</div>
