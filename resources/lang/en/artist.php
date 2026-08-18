<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

return [
    'page_description' => 'Featured Artists on Delta',
    'title' => 'Featured Artists',

    'admin' => [
        'hidden' => 'ARTIST IS CURRENTLY HIDDEN',
    ],

    'beatmaps' => [
        '_' => 'Beatmaps',
        'download' => 'download beatmap template',
        'download-na' => 'beatmap template not yet available',
    ],

    'index' => [
        'description' => 'Featured Artists are artists that we are working in collaboration with in order to bring new and original music to Delta. These artists and a selection of their tracks have been hand-picked by the Delta team as being awesomesauce and suitable for mapping. Some of these Featured Artists have also created exclusive new tracks for use in Delta.<br><br>All tracks in this section are provided as pre-timed .osz files and have been officially licensed for use in Delta and Delta-related content.',
    ],

    'links' => [
        'beatmaps' => 'Delta Beatmaps',
        'osu' => 'Delta Profile',
        'site' => 'Official Website',
    ],

    'songs' => [
        '_' => 'Songs',
        'count' => ':count_delimited song|:count_delimited songs',
        'original' => 'Delta original',
        'original_badge' => 'ORIGINAL',
    ],

    'tracklist' => [
        'title' => 'title',
        'length' => 'length',
        'bpm' => 'bpm',
        'genre' => 'genre',
    ],

    'tracks' => [
        'index' => [
            '_' => 'track search',

            'exclusive_only' => [
                'all' => 'All',
                'exclusive_only' => 'Delta original',
            ],

            'form' => [
                'advanced' => 'Advanced Search',
                'album' => 'Album',
                'artist' => 'Artist',
                'bpm_gte' => 'BPM Minimum',
                'bpm_lte' => 'BPM Maximum',
                'empty' => 'No tracks matching search criteria were found.',
                'exclusive_only' => 'Type',
                'genre' => 'Genre',
                'genre_all' => 'All',
                'length_gte' => 'Length Minimum',
                'length_lte' => 'Length Maximum',
            ],
        ],
    ],
];
