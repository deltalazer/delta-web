<?php

// Copyright (c) ppy Pty Ltd <contact@ppy.sh>. Licensed under the GNU Affero General Public License v3.0.
// See the LICENCE file in the repository root for full licence text.

declare(strict_types=1);

namespace App\Libraries;

use App\Exceptions\InvariantException;
use App\Models\Forum\Forum;

class DeltaForums
{
    const int TYPE_CATEGORY = 0;
    const int TYPE_FORUM = 1;

    public static function apply(?Forum $forum, string $action, array $params): string
    {
        switch ($action) {
            case 'create':
                return static::create($params);

            case 'rename':
                static::assertForum($forum);
                $name = presence(trim($params['name'] ?? ''));

                if ($name === null) {
                    throw new InvariantException('a name is required');
                }

                $forum->update([
                    'forum_name' => $name,
                    'forum_desc' => trim($params['description'] ?? ''),
                ]);

                return "renamed to {$name}";

            case 'delete':
                static::assertForum($forum);

                if ($forum->subforums()->exists()) {
                    throw new InvariantException('remove the subforums first');
                }

                if ($forum->topics()->exists()) {
                    throw new InvariantException('this forum still has topics');
                }

                $name = $forum->forum_name;
                $forum->delete();

                return "deleted {$name}";

            case 'move-up':
                static::assertForum($forum);

                return static::move($forum, -1);

            case 'move-down':
                static::assertForum($forum);

                return static::move($forum, 1);

            default:
                throw new InvariantException("unknown action: {$action}");
        }
    }

    private static function assertForum(?Forum $forum): void
    {
        if ($forum === null) {
            throw new InvariantException('no forum selected');
        }
    }

    private static function create(array $params): string
    {
        $name = presence(trim($params['name'] ?? ''));

        if ($name === null) {
            throw new InvariantException('a name is required');
        }

        $parentId = get_int($params['parent_id'] ?? null) ?? 0;

        if ($parentId !== 0) {
            $parent = Forum::find($parentId);

            if ($parent === null) {
                throw new InvariantException('the selected category no longer exists');
            }

            if ($parent->forum_type !== static::TYPE_CATEGORY) {
                throw new InvariantException('forums can only be created inside a category');
            }
        }

        $forum = new Forum();
        $forum->forum_name = $name;
        $forum->forum_desc = trim($params['description'] ?? '');
        $forum->forum_rules = '';
        $forum->parent_id = $parentId;
        $forum->forum_type = $parentId === 0 ? static::TYPE_CATEGORY : static::TYPE_FORUM;
        $forum->left_id = static::nextLeftId($parentId);
        $forum->right_id = 0;
        $forum->save();

        return $parentId === 0 ? "created category {$name}" : "created forum {$name}";
    }

    private static function nextLeftId(int $parentId): int
    {
        return (int) Forum::where('parent_id', $parentId)->max('left_id') + 10;
    }

    private static function move(Forum $forum, int $direction): string
    {
        $siblings = Forum::where('parent_id', $forum->parent_id)
            ->orderBy('left_id')
            ->orderBy('forum_id')
            ->get();

        $index = $siblings->search(fn (Forum $sibling): bool => $sibling->getKey() === $forum->getKey());
        $target = $index + $direction;

        if ($target < 0 || $target >= $siblings->count()) {
            throw new InvariantException('already at the end');
        }

        foreach ($siblings as $position => $sibling) {
            $sibling->update(['left_id' => ($position + 1) * 10]);
        }

        $swap = $siblings[$target];
        $forumLeft = $forum->fresh()->left_id;
        $swapLeft = $swap->fresh()->left_id;

        $forum->update(['left_id' => $swapLeft]);
        $swap->update(['left_id' => $forumLeft]);

        return $direction < 0 ? 'moved up' : 'moved down';
    }
}
