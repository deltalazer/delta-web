<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

return [
    'support' => [
        'convinced' => [
            'title' => 'I\'m convinced! :D',
            'support' => 'Star delta!lazer on GitHub',
            'gift' => 'it is free, and it is the whole ask',
            'instructions' => 'click the star to open the delta!lazer repository',
            'link_github' => 'Link GitHub Account',
        ],
        'delta-features' => [
            'section_gimmicks' => [
                'title' => 'Section Gimmicks',
                'description' => 'Set custom gameplay rules for each section of your map. Control HP behavior, judgment limits, forced mods, and more. Each section can have completely different rules.',
            ],
            'hp_gimmicks' => [
                'title' => 'HP Gimmicks',
                'description' => 'Take control of health mechanics. Set custom HP values for each judgment, or use Reverse HP mode where inaccurate hits can heal and perfects drain.',
            ],
            'count_limits' => [
                'title' => 'Count Limits',
                'description' => 'Limit how many 100s, 50s, or even 300s a player can get per section. Challenge and push players to their limits.',
            ],
            'forced_mods' => [
                'title' => 'Forced Mods',
                'description' => 'Force specific mods for individual sections. Create maps where HD activates during choruses, or HR for the drop.',
            ],
            'difficulty_overrides' => [
                'title' => 'Difficulty Overrides',
                'description' => 'Override approach rate, overall difficulty, and circle size on a per-section or per-hitobject basis.',
            ],
            'offset_penalty' => [
                'title' => 'Great Offset Penalty',
                'description' => 'Punish imprecise 300s, hitting within the 300 window but outside your custom threshold costs HP.',
            ],
            'uncapped_sv' => [
                'title' => 'Uncapped SV',
                'description' => '10x legacy SV cap is removed, allowing far more extreme SV control for advanced mapping.',
            ],
        ],
        'why-support' => [
            'title' => 'Why should I star delta!lazer?',

            'team' => [
                'title' => 'Support the Team',
                'description' => 'A small volunteer team builds and runs Delta. Stars are how we know it is worth continuing.',
            ],
            'visibility' => [
                'title' => 'Help People Find It',
                'description' => 'Stars are most of what GitHub uses to decide what to put in front of people. The more Delta has, the more players come across it.',
            ],
            'free' => [
                'title' => 'Nothing to Buy',
                'description' => 'Delta has no ads, no sponsors and nothing for sale. A star is the whole ask.',
            ],
            'development' => [
                'title' => 'Follow Development',
                'description' => 'Every change lands in the open, and each release is published as it happens.',
                'link_text' => 'Browse the repository &raquo;',
            ],
        ],
        'perks' => [
            'title' => 'Cool! What perks do I get?',
            'osu_direct' => [
                'title' => 'Delta direct',
                'description' => 'Gain quick and easy access to search for and download beatmaps without having to leave the game.',
            ],

            'friend_ranking' => [
                'title' => 'Friend Ranking',
                'description' => "See how you stack up against your friends on a beatmap's leaderboard, both in-game and on the website.",
            ],

            'country_ranking' => [
                'title' => 'Country Ranking',
                'description' => 'Conquer your country before you conquer the world.',
            ],

            'mod_filtering' => [
                'title' => 'Filter by Mods',
                'description' => 'Associate only with people who play HDHR? No problem!',
            ],

            'auto_downloads' => [
                'title' => 'Automatic Downloads',
                'description' => 'Beatmaps will automatically download in multiplayer games, while spectating others, or when clicking relevant links in chat!',
            ],

            'upload_more' => [
                'title' => 'Upload More',
                'description' => 'Additional pending beatmap slots (per ranked beatmap) up to a max of 10.',
            ],

            'early_access' => [
                'title' => 'Early Access',
                'description' => 'Gain early access to new releases with new features before they go public!<br/><br/>This includes early access to new features on the website too!',
            ],

            'customisation' => [
                'title' => 'Customisation',
                'description' => "Stand out by uploading a custom cover image, creating a fully customizable 'me!' section, or even change the colour to any of your liking within your user profile.",
            ],

            'beatmap_filters' => [
                'title' => 'Beatmap Filters',
                'description' => 'Filter beatmap searches by played and unplayed maps, or by rank achieved.',
            ],

            'yellow_fellow' => [
                'title' => 'Yellow Fellow',
                'description' => 'Be recognised in-game with your new bright yellow chat username colour.',
            ],

            'speedy_downloads' => [
                'title' => 'Speedy Downloads',
                'description' => 'More lenient download restrictions, especially when using Delta direct.',
            ],

            'skinnables' => [
                'title' => 'Skinnables',
                'description' => 'Extra in-game skinnables, like the main menu background.',
            ],

            'feature_votes' => [
                'title' => 'Feature Votes',
                'description' => 'Votes for feature requests. (2 per month)',
            ],

            'sort_options' => [
                'title' => 'Sort Options',
                'description' => 'The ability to view beatmap country / friend / mod-specific rankings in-game.',
            ],

            'more_favourites' => [
                'title' => 'More Favourites',
                'description' => 'The maximum number of beatmaps you can favourite is increased from :normally &rarr; :supporter',
            ],
            'more_friends' => [
                'title' => 'More Friends',
                'description' => 'The maximum number of friends you can have is increased from :normally &rarr; :supporter',
            ],
            'more_beatmaps' => [
                'title' => 'Upload More Beatmaps',
                'description' => 'How many pending beatmaps you can have at once is calculated from a base value plus an additional bonus for each ranked beatmap you currently have (up to a limit).<br/><br/>Normally this is :base plus :bonus per ranked beatmap (up to :bonus_max). With supporter, this increases to :supporter_base plus :supporter_bonus per ranked beatmap (up to :supporter_bonus_max).',
            ],
            'friend_filtering' => [
                'title' => 'Friend Leaderboards',
                'description' => 'Compete with your friends and see how you rank up against them!',
            ],

        ],
        'supporter_status' => [
            'contribution_with_duration' => 'Thank you for your ongoing support! So far, you\'ve contributed a total of :dollars, earning you the "Supporter" tag for :duration.',
            'not_yet' => "You haven't ever had a Delta supporter tag :(",
            'valid_until' => 'Your current Delta supporter tag is valid until :date!',
            'was_valid_until' => 'Your Delta supporter tag was valid until :date.',

            'gifted' => [
                '_' => 'Out of your total contributions, you’ve gifted :dollars worth of tags to :users covering :duration. That’s incredibly generous!',
                'users' => ':count_delimited other user|:count_delimited other users',
            ],
        ],
    ],
];
