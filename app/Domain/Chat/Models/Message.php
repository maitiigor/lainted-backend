<?php

namespace App\Domain\Chat\Models;

use App\Domain\Negotiation\Models\Offer;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use App\Support\Enums\MessageType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasUuid;

    // Raw disk path isn't useful to a client - `image_url` (below) is the
    // full, host-prefixed link they should render, same pattern as
    // ListingPhoto::url().
    protected $hidden = [
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'offer_id',
        'type',
        'body',
        'image_path',
        'is_flagged',
        'flagged_reason',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'is_flagged' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? request()?->getSchemeAndHttpHost().'/storage/'.$this->image_path
                : null,
        );
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}
