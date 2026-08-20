<?php

use App\Domain\Chat\Models\Conversation;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('conversation.{uuid}', function (User $user, string $uuid) {
    $conversation = Conversation::where('uuid', $uuid)->first();

    return $conversation && in_array($user->id, [$conversation->buyer_id, $conversation->seller_id], true);
});
