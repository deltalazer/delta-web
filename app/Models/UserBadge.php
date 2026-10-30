<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

namespace App\Models;

/**
 * @property \Carbon\Carbon|null $awarded
 * @property string $description
 * @property string $image
 * @property string $url
 * @property int $user_id
 */
class UserBadge extends Model
{
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = ['awarded' => 'datetime'];
    protected $primaryKey = 'user_id';
    protected $table = 'osu_badges';

    public function image2xUrl()
    {
        if ($this->isExternalImage()) {
            return $this->image;
        }

        return retinaify($this->imageUrl());
    }

    public function imageUrl()
    {
        if ($this->isExternalImage()) {
            return $this->image;
        }

        return "https://assets.ppy.sh/profile-badges/{$this->image}";
    }

    private function isExternalImage(): bool
    {
        return preg_match('~^https?://~', $this->image) === 1;
    }
}
