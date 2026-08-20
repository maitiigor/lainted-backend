<?php

namespace App\Domain\Trust\Models;

use App\Domain\Chat\Models\ContactLeakStrike;
use App\Domain\Users\Models\User;
use App\Support\Enums\IssuedBy;
use App\Support\Enums\StrikeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Strike extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'reason',
        'reference_type',
        'reference_id',
        'issued_by',
        'admin_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => StrikeType::class,
            'issued_by' => IssuedBy::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function contactLeakStrikes(): HasMany
    {
        return $this->hasMany(ContactLeakStrike::class);
    }
}
