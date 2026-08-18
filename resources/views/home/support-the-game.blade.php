{{--
    Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
    See the LICENCE file in the repository root for full licence text.
--}}
@extends('master')

@section('content')
    @component('layout._page_header_v4', ['params' => [
        'showTitle' => false,
        'theme' => 'supporter',
    ]])
        @slot('contentAppend')
            <div class="supporter-status">
                <div class="supporter-status__pippi"></div>
            </div>
        @endslot
    @endcomponent

    <div class="osu-page osu-page--supporter">
        <div class="supporter">
            @auth
                <div class="supporter-eyecatch">
                    <div class="supporter-eyecatch__box">
                        <a
                            class="btn-osu-big btn-osu-big--rounded-thin"
                            href="https://github.com/deltalazer/delta"
                            rel="noreferrer"
                            target="_blank"
                        >
                            <span class="btn-osu-big__content">
                                <span class="btn-osu-big__left">
                                    <span class="btn-osu-big__text-top">
                                        {{ osu_trans('community.support.convinced.support') }}
                                    </span>
                                </span>

                                <span class="btn-osu-big__icon">
                                    <span class="fas fa-star"></span>
                                </span>
                            </span>
                        </a>

                        @if ($canLinkGithub)
                            <div class="supporter-eyecatch__text">
                                <a
                                    class="btn-osu-big btn-osu-big--rounded-thin"
                                    href="{{ route('account.github-users.create') }}"
                                >
                                    <span class="btn-osu-big__content">
                                        <span class="btn-osu-big__left">
                                            <span class="btn-osu-big__text-top">
                                                {{ osu_trans('community.support.convinced.link_github') }}
                                            </span>
                                        </span>

                                        <span class="btn-osu-big__icon">
                                            <span class="fab fa-github"></span>
                                        </span>
                                    </span>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endauth

            <h3 class="supporter__title">
                {{ osu_trans('community.support.why-support.title') }}
            </h3>
            @include('home._supporter_perk_group', ['group' => $data['support-reasons']])
            <div class="supporter__block supporter__block--bg-0">
                <h3 class="supporter__title">
                    {{ osu_trans('community.support.perks.title') }}
                </h3>
            </div>
            @foreach($data['perks'] as $index => $group)
                <div class="supporter__block supporter__block--{{'bg-'.$index % 3}}">
                    @include("home._supporter_perk_{$group['type']}", ['group' => $group])
                </div>
            @endforeach
            <h3 class="supporter__title supporter__title--convinced">
                {{ osu_trans('community.support.convinced.title') }}
            </h3>
            <div class="supporter-eyecatch">
                <div class="supporter-eyecatch__box">
                    <a
                        class="btn-osu-big btn-osu-big--rounded-thin"
                        href="https://github.com/deltalazer/delta"
                        rel="noreferrer"
                        target="_blank"
                    >
                        <span class="btn-osu-big__content">
                            <span class="btn-osu-big__left">
                                <span class="btn-osu-big__text-top">
                                    {{ osu_trans('community.support.convinced.support') }}
                                </span>
                            </span>

                            <span class="btn-osu-big__icon">
                                <span class="fas fa-star"></span>
                            </span>
                        </span>
                    </a>

                    <div class="supporter-eyecatch__text supporter-eyecatch__text--sub-1">
                        {{ osu_trans('community.support.convinced.gift') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
