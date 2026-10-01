<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Libraries;

use App\Exceptions\InvariantException;
use App\Libraries\User\AvatarHelper;
use App\Models\BeatmapDiscussion;
use App\Models\BeatmapDiscussionPost;
use App\Models\Beatmapset;
use App\Models\Group;
use App\Models\Rank;
use App\Models\User;
use App\Models\UserAccountHistory;
use App\Models\UserBadge;
use Illuminate\Http\UploadedFile;

class DeltaModeration
{
    const ADMIN_USER_ACTIONS = ['badge_add', 'badge_remove', 'group_add', 'group_remove', 'title_clear', 'title_create', 'title_delete', 'title_set', 'title_update'];
    const BEATMAPSET_ACTIONS = ['graveyard', 'love', 'pending', 'qualify', 'rank', 'unrank'];
    const MODERATOR_USER_ACTIONS = ['note', 'restrict', 'silence', 'unrestrict'];
    const SESSION_KEY = 'delta_admin_controls';
    const SUPER_ADMIN_USER_ACTIONS = ['avatar_remove', 'username'];

    public static function controlsEnabled(): bool
    {
        return session(static::SESSION_KEY) === true;
    }

    public static function canBeatmapsetAction(User $actor, string $action): bool
    {
        return in_array($action, static::BEATMAPSET_ACTIONS, true) && $actor->isAdmin();
    }

    public static function canUserAction(User $actor, string $action): bool
    {
        if (in_array($action, static::SUPER_ADMIN_USER_ACTIONS, true)) {
            return $actor->isSuperAdmin();
        }

        if (in_array($action, static::ADMIN_USER_ACTIONS, true)) {
            return $actor->isAdmin();
        }

        return in_array($action, static::MODERATOR_USER_ACTIONS, true)
            && ($actor->isAdmin() || $actor->isModerator());
    }

    public static function applyBeatmapsetAction(Beatmapset $beatmapset, User $actor, string $action, array $params): string
    {
        if (!static::canBeatmapsetAction($actor, $action)) {
            throw new InvariantException("not allowed: {$action}");
        }

        switch ($action) {
            case 'rank':
                if (!$beatmapset->rank()) {
                    throw new InvariantException($beatmapset->isQualified()
                        ? 'cannot rank while there are open issues'
                        : 'only qualified maps can be ranked');
                }

                return 'ranked';

            case 'qualify':
                $beatmapset->qualify($actor);

                return 'qualified';

            case 'love':
                $beatmapset->love($actor);

                return 'loved';

            case 'unrank':
                static::unrank($beatmapset, $actor, presence($params['reason'] ?? null));

                return 'unranked, now pending';

            case 'graveyard':
                $beatmapset->update(['approved' => Beatmapset::STATES['graveyard']]);

                return 'moved to graveyard';

            case 'pending':
                $beatmapset->update(['approved' => Beatmapset::STATES['pending']]);

                return 'moved to pending';
        }

        throw new InvariantException("unknown action: {$action}");
    }

    public static function applyUserAction(User $user, User $actor, string $action, array $params): string
    {
        if (!static::canUserAction($actor, $action)) {
            throw new InvariantException("not allowed: {$action}");
        }

        switch ($action) {
            case 'group_add':
                $user->addToGroup(Group::findOrFail(get_int($params['group_id'] ?? null)), null, $actor);

                return 'added to group';

            case 'group_remove':
                $user->removeFromGroup(Group::findOrFail(get_int($params['group_id'] ?? null)), $actor);

                return 'removed from group';

            case 'badge_add':
                $description = presence(trim($params['description'] ?? ''));
                $image = static::badgeImage($params);

                if ($image === null || $description === null) {
                    throw new InvariantException('an image and description are required');
                }

                UserBadge::create([
                    'awarded' => now(),
                    'description' => $description,
                    'image' => $image,
                    'url' => static::normaliseUrl($params['url'] ?? null),
                    'user_id' => $user->getKey(),
                ]);

                return "badge added: {$description}";

            case 'badge_remove':
                $image = presence(trim($params['image'] ?? ''));
                $removed = UserBadge::where('user_id', $user->getKey())->where('image', $image)->delete();

                if ($removed === 0) {
                    throw new InvariantException('that badge no longer exists');
                }

                return 'badge removed';

            case 'title_create':
                $title = presence(trim($params['rank_title'] ?? ''));

                if ($title === null) {
                    throw new InvariantException('a title is required');
                }

                $rank = new Rank();
                $rank->rank_title = $title;
                $rank->url = static::normaliseUrl($params['url'] ?? null);
                $rank->rank_special = 1;
                $rank->save();

                $user->update(['user_rank' => $rank->getKey()]);

                return "title created and applied: {$title}";

            case 'title_update':
                $rank = Rank::findOrFail(get_int($params['rank_id'] ?? null));
                $title = presence(trim($params['rank_title'] ?? '')) ?? $rank->rank_title;

                $rank->update([
                    'rank_title' => $title,
                    'url' => static::normaliseUrl($params['url'] ?? null),
                ]);

                return "title updated: {$title}";

            case 'title_delete':
                $rank = Rank::findOrFail(get_int($params['rank_id'] ?? null));
                $title = $rank->rank_title;
                $holders = User::where('user_rank', $rank->getKey())->count();

                User::where('user_rank', $rank->getKey())->update(['user_rank' => null]);
                $rank->delete();

                return "deleted title {$title} (cleared from {$holders} user(s))";

            case 'title_set':
                $rank = Rank::findOrFail(get_int($params['rank_id'] ?? null));
                $user->update(['user_rank' => $rank->getKey()]);

                return "title set to {$rank->rank_title}";

            case 'title_clear':
                $user->update(['user_rank' => null]);

                return 'title cleared';

            case 'restrict':
                $user->update(['user_warnings' => 1]);

                return 'restricted';

            case 'unrestrict':
                $user->update(['user_warnings' => 0]);

                return 'restriction lifted';

            case 'silence':
                UserAccountHistory::create([
                    'ban_status' => UserAccountHistory::TYPES['silence'],
                    'banner_id' => $actor->getKey(),
                    'period' => get_int($params['hours'] ?? null) ?? 1,
                    'reason' => presence($params['reason'] ?? null) ?? 'no reason given',
                    'timestamp' => now(),
                    'user_id' => $user->getKey(),
                ]);

                return 'silenced';

            case 'note':
                UserAccountHistory::addNote($user, presence($params['reason'] ?? null) ?? '', $actor);

                return 'note added';

            case 'username':
                $username = presence($params['username'] ?? null);

                if ($username === null) {
                    throw new InvariantException('a new username is required');
                }

                $user->changeUsername($username, 'admin');

                return "renamed to {$username}";

            case 'avatar_remove':
                AvatarHelper::set($user, null);

                return 'avatar removed';
        }

        throw new InvariantException("unknown action: {$action}");
    }

    private static function unrank(Beatmapset $beatmapset, User $actor, ?string $reason): void
    {
        if ($reason === null) {
            throw new InvariantException('unranking needs a reason - it is posted publicly');
        }

        $discussion = new BeatmapDiscussion([
            'beatmapset_id' => $beatmapset->getKey(),
            'message_type' => 'problem',
            'resolved' => false,
            'user_id' => $actor->getKey(),
        ]);

        $post = new BeatmapDiscussionPost([
            'message' => $reason,
            'user_id' => $actor->getKey(),
        ]);

        $discussion->getConnection()->transaction(function () use ($actor, $beatmapset, $discussion, $post) {
            $discussion->saveOrExplode();
            $post->beatmap_discussion_id = $discussion->getKey();
            $post->saveOrExplode();

            $beatmapset->disqualifyOrResetNominations($actor, $discussion, true);
        });
    }

    private static function normaliseUrl(?string $url): string
    {
        $url = trim($url ?? '');

        if ($url === '' || preg_match('~^(https?://|/)~i', $url) === 1) {
            return $url;
        }

        return "https://{$url}";
    }

    private static function badgeImage(array $params): ?string
    {
        $file = $params['image_file'] ?? null;

        if (!($file instanceof UploadedFile)) {
            return presence(trim($params['image'] ?? ''));
        }

        if (!$file->isValid()) {
            throw new InvariantException('the upload did not complete');
        }

        if (strpos((string) $file->getMimeType(), 'image/') !== 0) {
            throw new InvariantException('that file is not an image');
        }

        if ($file->getSize() > 1_000_000) {
            throw new InvariantException('badge images must be under 1MB');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $name = bin2hex(random_bytes(8)).($extension === '' ? '' : ".{$extension}");
        \Storage::putFileAs('badges', $file, $name);

        return $GLOBALS['cfg']['app']['url']."/uploads/default/badges/{$name}";
    }
}
