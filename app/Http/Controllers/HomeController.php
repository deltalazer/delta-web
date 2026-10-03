<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Http\Controllers;

use App;
use App\Libraries\CurrentStats;
use App\Libraries\MenuContent;
use App\Libraries\Search\AllSearch;
use App\Libraries\Search\QuickSearch;
use App\Models\Beatmap;
use App\Models\BeatmapDownload;
use App\Models\Beatmapset;
use App\Models\Build;
use App\Models\Forum\Post;
use App\Models\GithubUser;
use App\Models\LivestreamCollection;
use App\Models\Multiplayer\Room;
use App\Models\NewsPost;
use App\Models\Solo\Score;
use App\Models\Team;
use App\Models\User;
use App\Transformers\MenuImageTransformer;
use App\Transformers\NewsPostTransformer;
use Auth;
use Carbon\CarbonImmutable;
use DeviceDetector\DeviceDetector;
use Request;

/**
 * @group Home
 */
class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', [
            'only' => [
                'downloadQuotaCheck',
                'quickSearch',
            ],
        ]);

        $this->middleware('require-scopes:public', ['only' => 'search']);

        parent::__construct();
    }

    public function bbcodePreview()
    {
        $post = new Post(['post_text' => Request::input('text')]);

        return $post->bodyHTML();
    }

    /**
     * @group Undocumented
     */
    public function downloadQuotaCheck()
    {
        return [
            'quota_used' => BeatmapDownload::where('user_id', Auth::user()->user_id)->count(),
        ];
    }

    public function getDownload()
    {
        $lazerPlatformNames = [
            'linux_x64' => 'Linux (x64)',
            'windows_x64' => osu_trans('home.download.os_version_or_later', ['os_version' => 'Windows 10']).' (x64)',
        ];

        $platform = get_string(request('platform'));
        if (!array_key_exists($platform, $lazerPlatformNames)) {
            $deviceDetector = new DeviceDetector(\Request::header('User-Agent') ?? '');
            $deviceDetector->parse();
            $family = $deviceDetector->getOs('family');

            $platform = match ($family) {
                'GNU/Linux' => 'linux_x64',
                default => 'windows_x64',
            };
        }

        $version = Build::where(['stream_id' => $GLOBALS['cfg']['osu']['client']['download_stream'], 'test_build' => false])
            ->orderBy('build_id', 'desc')
            ->first()
            ?->version;

        $items = [];
        foreach ($lazerPlatformNames as $key => $value) {
            $items[] = ['id' => $key, 'text' => $value];
        }

        $selectOptions = [
            'currentItem' => ['id' => $platform, 'text' => osu_trans('home.download.other_os')],
            'items' => $items,
            'modifiers' => 'download',
            'type' => 'download',
        ];

        return ext_view('home.download', [
            'lazerUrl' => osu_url("lazer_dl.{$platform}"),
            'lazerPlatformName' => $lazerPlatformNames[$platform],
            'selectOptions' => $selectOptions,
            'version' => $version,
        ]);
    }

    public function index()
    {
        $newsLimit = Auth::check() ? NewsPost::DASHBOARD_LIMIT + 1 : NewsPost::LANDING_LIMIT;
        $news = NewsPost::default()->limit($newsLimit)->get();

        if (Auth::check()) {
            $menuImages = json_collection(MenuContent::activeImages(), new MenuImageTransformer());
            $newBeatmapsets = Beatmapset::latestRanked();
            $popularBeatmapsetsByRuleset = Beatmapset::popularByRuleset();

            $dailyChallenge = Room::dailyChallengeFor(CarbonImmutable::now());

            $livestream = new LivestreamCollection();
            $featuredStream = $livestream->featured();

            return ext_view('home.user', compact(
                'menuImages',
                'newBeatmapsets',
                'news',
                'popularBeatmapsetsByRuleset',
                'dailyChallenge',
                'featuredStream',
            ));
        } else {
            $news = json_collection($news, new NewsPostTransformer());

            return ext_view('home.landing', ['stats' => new CurrentStats(), 'news' => $news]);
        }
    }

    public function messageUser($user)
    {
        return ujs_redirect(route('chat.index', ['sendto' => $user]));
    }

    public function sitemap()
    {
        $urls = [
            ['loc' => $GLOBALS['cfg']['app']['url'].'/'],
            ['loc' => route('download')],
        ];

        foreach (['Delta', 'Delta/Documentation', 'Delta/FAQ', 'Delta/Team'] as $wikiPath) {
            $urls[] = ['loc' => wiki_url($wikiPath, 'en')];
        }

        foreach (array_keys(Beatmap::MODES) as $mode) {
            $urls[] = ['loc' => route('rankings', ['mode' => $mode, 'type' => 'global', 'sort' => 'performance'])];
        }

        foreach (Build::default()->orderBy('date', 'DESC')->with('updateStream')->get() as $build) {
            $urls[] = ['loc' => build_url($build), 'lastmod' => $build->date];
        }

        $beatmapsets = Beatmapset::active()->where('approved', '>', 0)->where('user_id', '<>', 0)->orderBy('beatmapset_id')->get();

        foreach ($beatmapsets as $beatmapset) {
            $urls[] = ['loc' => route('beatmapsets.show', $beatmapset), 'lastmod' => $beatmapset->last_update];
        }

        $players = User::default()->whereIn('user_id', Score::select('user_id'))->orderBy('user_id')->pluck('user_id');

        foreach ($players as $userId) {
            $urls[] = ['loc' => route('users.show', ['user' => $userId])];
        }

        foreach (Team::orderBy('id')->pluck('id') as $teamId) {
            $urls[] = ['loc' => route('teams.show', ['team' => $teamId])];
        }

        return response()
            ->view('home.sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=utf-8');
    }

    public function opensearch()
    {
        return ext_view('home.opensearch', null, 'opensearch')->header('Cache-Control', 'max-age=86400');
    }

    public function quickSearch()
    {
        $quickSearch = new QuickSearch(Request::all(), ['user' => auth()->user()]);
        $searches = $quickSearch->searches();

        $result = [];

        if ($quickSearch->hasQuery()) {
            foreach ($searches as $mode => $search) {
                if ($search === null) {
                    continue;
                }
                $result[$mode]['total'] = $search->count();
                if (QuickSearch::MODES[$mode]['size'] !== 0) {
                    $transformer = QuickSearch::MODES[$mode]['transformer'];
                    $result[$mode]['items'] = json_collection(
                        $search->data(),
                        new $transformer['class'](),
                        $transformer['includes'],
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Search
     *
     * Searches users and wiki pages.
     *
     * ---
     *
     * ### Response Format
     *
     * Field     | Type                       | Description
     * --------- | -------------------------- | -----------
     * user      | SearchResult&lt;User>?     | For `all` or `user` mode. Only first 100 results are accessible
     * wiki_page | SearchResult&lt;WikiPage>? | For `all` or `wiki_page` mode
     *
     * #### SearchResult&lt;T>
     *
     * Field | Type    | Description
     * ----- | ------- | -----------
     * data  | T[]     | |
     * total | integer | |
     *
     * @queryParam mode string Either `all`, `user`, or `wiki_page`. Default is `all`. Example: all
     * @queryParam query Search keyword. Example: hello
     * @queryParam page Search result page. Ignored for mode `all`. Example: 1
     */
    public function search()
    {
        $currentUser = Auth::user();
        $allSearch = new AllSearch(Request::all(), ['user' => $currentUser]);

        switch ($allSearch->getMode()) {
            case 'beatmapset':
                return ujs_redirect(route('beatmapsets.index', ['q' => $allSearch->getRawQuery()]));
        }

        $isSearchPage = true;

        if (is_api_request()) {
            return response()->json($allSearch->toJson());
        }

        $fields = $currentUser?->isModerator() ?? false ? [] : ['includeDeleted' => null];

        return ext_view('home.search', compact('allSearch', 'fields', 'isSearchPage'));
    }

    public function setLocale()
    {
        $newLocale = get_valid_locale(Request::input('locale')) ?? $GLOBALS['cfg']['app']['fallback_locale'];
        App::setLocale($newLocale);

        if (Auth::check()) {
            Auth::user()->update([
                'user_lang' => $newLocale,
            ]);
        }

        return ext_view('layout.ujs_full_reload', [], 'js')
            ->withCookie(cookie()->forever('locale', $newLocale));
    }

    public function supportTheGame()
    {
        $user = auth()->user();
        $canLinkGithub = $user !== null
            && GithubUser::canAuthenticate()
            && !$user->githubUser()->exists();

        $pageLayout = [
            // why support
            'support-reasons' => [
                'type' => 'group',
                'section' => 'why-support',
                'items' => [
                    'team' => [
                        'icons' => ['fas fa-users'],
                    ],
                    'visibility' => [
                        'icons' => ['fas fa-search'],
                    ],
                    'free' => [
                        'icons' => ['fas fa-ad', 'fas fa-slash'],
                    ],
                    'development' => [
                        'icons' => ['fas fa-code-branch'],
                        'link' => 'https://github.com/deltalazer/delta',
                    ],
                ],
            ],

            // supporter perks

            // There are 5 perk rendering types: image, image-flipped, hero, group and image-group.
            // image, image-flipped, hero each show an individual perk (with image) while group and image-group show groups of perks (the latter with images)
            'perks' => [
                [
                    'type' => 'image',
                    'name' => 'osu_direct',
                    'icons' => ['fas fa-search'],
                ],
                [
                    'type' => 'image_group',
                    'items' => [
                        'friend_ranking' => [
                            'icons' => ['fas fa-list-alt'],
                        ],
                        'country_ranking' => [
                            'icons' => ['fas fa-globe-asia'],
                        ],
                        'mod_filtering' => [
                            'icons' => ['fas fa-tasks'],
                        ],
                    ],
                ],
                [
                    'type' => 'image',
                    'variant' => 'flipped',
                    'name' => 'beatmap_filters',
                    'icons' => ['fas fa-filter'],
                ],
                [
                    'type' => 'group',
                    'items' => [
                        'auto_downloads' => [
                            'icons' => ['fas fa-download'],
                        ],
                        'more_beatmaps' => [
                            'icons' => ['fas fa-file-upload'],
                            'translation_options' => [
                                'base' => $GLOBALS['cfg']['osu']['beatmapset']['upload_allowed'],
                                'bonus' => $GLOBALS['cfg']['osu']['beatmapset']['upload_bonus_per_ranked'],
                                'bonus_max' => $GLOBALS['cfg']['osu']['beatmapset']['upload_bonus_per_ranked_max'],
                                'supporter_base' => $GLOBALS['cfg']['osu']['beatmapset']['upload_allowed_supporter'],
                                'supporter_bonus' => $GLOBALS['cfg']['osu']['beatmapset']['upload_bonus_per_ranked_supporter'],
                                'supporter_bonus_max' => $GLOBALS['cfg']['osu']['beatmapset']['upload_bonus_per_ranked_max_supporter'],
                            ],
                        ],
                        'early_access' => [
                            'icons' => ['fas fa-flask'],
                        ],
                    ],
                ],
                [
                    'type' => 'hero',
                    'name' => 'customisation',
                    'icons' => ['fas fa-image'],
                ],
                [
                    'type' => 'group',
                    'items' => [
                        'more_favourites' => [
                            'icons' => ['fas fa-star'],
                            'translation_options' => [
                                'normally' => $GLOBALS['cfg']['osu']['beatmapset']['favourite_limit'],
                                'supporter' => $GLOBALS['cfg']['osu']['beatmapset']['favourite_limit_supporter'],
                            ],
                        ],
                        'more_friends' => [
                            'icons' => ['fas fa-user-friends'],
                            'translation_options' => [
                                'normally' => $GLOBALS['cfg']['osu']['user']['max_friends'],
                                'supporter' => $GLOBALS['cfg']['osu']['user']['max_friends_supporter'],
                            ],
                        ],
                        'friend_filtering' => [
                            'icons' => ['fas fa-medal'],
                        ],
                    ],
                ],
                [
                    'type' => 'image_group',
                    'items' => [
                        'yellow_fellow' => [
                            'icons' => ['fas fa-fire'],
                        ],
                        'speedy_downloads' => [
                            'icons' => ['fas fa-tachometer-alt'],
                        ],
                        'skinnables' => [
                            'icons' => ['fas fa-paint-brush'],
                        ],
                    ],
                ],
            ],
        ];

        return ext_view('home.support-the-game', [
            'canLinkGithub' => $canLinkGithub,
            'data' => $pageLayout,
        ]);
    }

    public function testflight()
    {
        return ext_view('home.testflight');
    }
}
