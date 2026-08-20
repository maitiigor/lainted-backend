<?php

namespace App\Domain\Locations\Models;

use App\Domain\Listings\Models\Listing;
use App\Domain\Orders\Models\DeliveryAddress;
use App\Domain\Users\Models\User;
use App\Support\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lga extends Model
{
    use HasUuid;

    protected $fillable = [
        'state',
        'name',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function deliveryAddresses(): HasMany
    {
        return $this->hasMany(DeliveryAddress::class);
    }
}
