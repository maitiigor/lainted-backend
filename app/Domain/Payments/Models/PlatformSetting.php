<?php

namespace App\Domain\Payments\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton settings row (always id=1, seeded by its migration) - the
 * admin-configurable knobs this project has, kept hand-rolled rather than
 * via a settings package, matching the project's "no laravel-paystack
 * package"-style preference for avoiding dependencies for simple things.
 */
class PlatformSetting extends Model
{
    protected $fillable = [
        'personal_commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'personal_commission_rate' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrFail();
    }
}
