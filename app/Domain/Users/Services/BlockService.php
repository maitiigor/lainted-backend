<?php

namespace App\Domain\Users\Services;

use App\Domain\Users\Exceptions\BlockException;
use App\Domain\Users\Models\User;

class BlockService
{
    public function block(User $blocker, User $target, ?string $reason): void
    {
        if ($blocker->id === $target->id) {
            throw new BlockException('You cannot block yourself.');
        }

        $blocker->blockedUsers()->syncWithoutDetaching([
            $target->id => ['reason' => $reason],
        ]);
    }

    public function unblock(User $blocker, User $target): void
    {
        $blocker->blockedUsers()->detach($target->id);
    }

    public static function isBlockedEitherWay(User $a, User $b): bool
    {
        return $a->blockedUsers()->where('users.id', $b->id)->exists()
            || $b->blockedUsers()->where('users.id', $a->id)->exists();
    }
}
