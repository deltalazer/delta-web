{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@foreach (['delta_error' => 'fas fa-exclamation', 'delta_notice' => 'fas fa-check'] as $flash => $icon)
    @if (session($flash) !== null)
        <div class="admin-menu-item">
            <span class="admin-menu-item__content">
                <span class="admin-menu-item__label admin-menu-item__label--icon">
                    <span class="{{ $icon }}"></span>
                </span>

                <span class="admin-menu-item__label admin-menu-item__label--text">
                    {{ session($flash) }}
                </span>
            </span>
        </div>
    @endif
@endforeach
