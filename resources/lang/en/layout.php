<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

return [
    'audio' => [
        'autoplay' => 'Play next track automatically',
    ],

    'defaults' => [
        'page_description' => 'delta!lazer (deltalazer) is a community-driven fork of osu!lazer with section gimmicks and hit object control.',
    ],

    'download_notice' => [
        'message' => 'This is an early release of delta!lazer. It has not been officially released yet.',
        'title' => 'Early release',
    ],

    'moved_notice' => [
        'message' => 'You have been redirected to the new home of delta!lazer.',
        'title' => 'New home',
    ],

    'copy_notice' => [
        'original' => 'View the original on osu.ppy.sh.',

        'legal' => [
            'message' => 'These are osu!\'s legal documents, copied unchanged from the osu! wiki. They describe ppy\'s services, not delta!lazer, which does not have its own yet. :link',
            'title' => 'Not delta!lazer\'s terms',
        ],

        'wiki' => [
            'message' => 'This is a copy of the osu! wiki. It hasn\'t been adapted for delta!lazer yet, so rules, features and links may not match this server. :link',
            'title' => 'osu! wiki',
        ],
    ],

    'header' => [
        'admin' => [
            'beatmapset' => 'beatmapset',
            'beatmapset_covers' => 'beatmapset covers',
            'contest' => 'contest',
            'contests' => 'contests',
            'root' => 'console',
        ],

        'artists' => [
            'index' => 'listing',
        ],

        'beatmapsets' => [
            'show' => 'info',
            'discussions' => 'discussion',
            'versions' => 'version history',
        ],

        'changelog' => [
            'index' => 'listing',
        ],

        'help' => [
            'index' => 'index',
            'sitemap' => 'Sitemap',
        ],

        'store' => [
            'cart' => 'cart',
            'orders' => 'order history',
            'products' => 'products',
        ],

        'tournaments' => [
            'index' => 'listing',
        ],

        'users' => [
            'modding' => 'modding',
            'playlists' => 'playlists',
            'ranked-play' => 'ranked play',
            'realtime' => 'multiplayer',
            'show' => 'info',
        ],
    ],

    'gallery' => [
        'close' => 'Close (Esc)',
        'fullscreen' => 'Toggle fullscreen',
        'zoom' => 'Zoom in/out',
        'previous' => 'Previous (arrow left)',
        'next' => 'Next (arrow right)',
    ],

    'menu' => [
        'beatmaps' => [
            '_' => 'beatmaps',
        ],
        'community' => [
            '_' => 'community',
            'dev' => 'development',
        ],
        'help' => [
            '_' => 'help',
            'getAbuse' => 'report abuse',
            'getDocs' => 'documentation',
            'getFaq' => 'faq',
            'getRules' => 'rules',
            'getSupport' => 'no, really, i need help!',
        ],
        'home' => [
            '_' => 'home',
            'team' => 'team',
        ],
        'rankings' => [
            '_' => 'rankings',
        ],
        'store' => [
            '_' => 'store',
        ],
    ],

    'footer' => [
        'general' => [
            '_' => 'General',
            'home' => 'Home',
            'changelog-index' => 'Changelog',
            'beatmaps' => 'Beatmap Listing',
            'download' => 'Download delta!lazer',
        ],
        'help' => [
            '_' => 'Help & Community',
            'faq' => 'Frequently Asked Questions',
            'forum' => 'Community Forums',
            'livestreams' => 'Live Streams',
            'report' => 'Report an Issue',
            'wiki' => 'Wiki',
        ],
        'legal' => [
            '_' => 'Legal & Status',
            'copyright' => 'Copyright (DMCA)',
            'jp_sctl' => '特定商取引法',
            'privacy' => 'Privacy',
            'rules' => 'Rules',
            'server_status' => 'Server Status',
            'source_code' => 'Source Code',
            'terms' => 'Terms',
        ],
    ],

    'errors' => [
        '400' => [
            'error' => 'Invalid request parameter',
            'description' => '',
        ],
        '404' => [
            'error' => 'Page Missing',
            'description' => "Sorry, but the page you requested isn't here!",
        ],
        '403' => [
            'error' => "You shouldn't be here.",
            'description' => 'You could try going back, though.',
        ],
        '401' => [
            'error' => "You shouldn't be here.",
            'description' => 'You could try going back, though. Or maybe signing in.',
        ],
        '405' => [
            'error' => 'Page Missing',
            'description' => "Sorry, but the page you requested isn't here!",
        ],
        '422' => [
            'error' => 'Invalid request parameter',
            'description' => '',
        ],
        '429' => [
            'error' => 'Rate limit exceeded',
            'description' => '',
        ],
        '500' => [
            'error' => 'Oh no! Something broke! ;_;',
            'description' => "We're automatically notified of every error.",
        ],
        'fatal' => [
            'error' => 'Oh no! Something broke (badly)! ;_;',
            'description' => "We're automatically notified of every error.",
        ],
        '503' => [
            'error' => 'Down for maintenance!',
            'description' => "Maintenance usually takes anywhere from 5 seconds to 10 minutes. If we're down for longer, see :link for more information.",
            'link' => [
                'text' => '@osustatus',
                'href' => 'https://twitter.com/osustatus',
            ],
        ],
        // used by sentry if it returns an error
        'reference' => "Just in case, here's a code you can give to support!",
    ],

    'popup_login' => [
        'button' => 'sign in / register',

        'login' => [
            'forgot' => "I've forgotten my details",
            'password' => 'password',
            'title' => 'Sign In To Proceed',
            'username' => 'username',

            'error' => [
                'email' => "Username or email address doesn't exist",
                'password' => 'Incorrect password',
            ],
        ],

        'register' => [
            'download' => 'Download',
            'info' => 'Download delta!lazer to create your own account!',
            'title' => "Don't have an account?",
        ],
    ],

    'popup_user' => [
        'links' => [
            'account-edit' => 'Settings',
            'create_team' => 'Create Team',
            'follows' => 'Watchlists',
            'friends' => 'Friends',
            'legacy_score_only_toggle' => 'Lazer mode',
            'legacy_score_only_toggle_tooltip' => 'Lazer mode shows scores set from lazer with a new scoring algorithm',
            'logout' => 'Sign Out',
            'profile' => 'My Profile',
            'scoring_mode_toggle' => 'Classic scoring',
            'scoring_mode_toggle_tooltip' => 'Adjust score values to feel more like classic uncapped scoring',
            'team' => 'My Team',
        ],
    ],

    'popup_search' => [
        'initial' => 'Type to search!',
        'retry' => 'Search failed. Click to retry.',
    ],

    'popup_locale' => [
        'notice' => 'Delta!Lazer does not support other languages due to rebranding of the translations being required but not done yet.',
    ],
];
