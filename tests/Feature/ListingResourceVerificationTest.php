<?php

namespace Tests\Feature;

use App\Domain\Chat\Models\ContactLeakStrike;
use App\Domain\Listings\Models\Listing;
use App\Domain\Trust\Models\Strike;
use App\Domain\Users\Models\User;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\ListingResource\Pages\CreateListing;
use App\Filament\Resources\ListingResource\Pages\ListListings;
use App\Filament\Resources\ListingResource\Pages\ViewListing;
use App\Support\Enums\ListingStatus;
use Database\Seeders\CategorySeeder;
use Database\Seeders\LgaSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingResourceVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_view_and_create_pages_render_without_error(): void
    {
        $this->seed([RoleSeeder::class, LgaSeeder::class, CategorySeeder::class]);

        $admin = User::create([
            'name' => 'Temp Admin',
            'phone' => '+2340000000000',
        ]);
        $admin->assignRole('admin');

        $listing = Listing::create([
            'seller_id' => $admin->id,
            'category_id' => 2,
            'title' => 'Verification listing',
            'description' => 'Just checking the admin panel renders.',
            'item_type' => 'fashion',
            'condition_grade' => 'A',
            'weight_tier' => 'standard',
            'price' => 1000,
            'lga_id' => 11,
            'listing_source' => 'wardrobe',
            'status' => ListingStatus::Active,
        ]);

        $listing->photos()->create([
            'original_path' => 'listings/originals/fake.jpg',
            'watermarked_path' => 'listings/watermarked/fake.jpg',
            'position' => 0,
        ]);

        $strike = Strike::create([
            'user_id' => $admin->id,
            'type' => 'contact_leak',
            'reason' => 'Contact info detected in listing text.',
            'reference_type' => 'listing',
            'reference_id' => $listing->id,
            'issued_by' => 'system',
        ]);

        ContactLeakStrike::create([
            'user_id' => $admin->id,
            'strike_id' => $strike->id,
            'source_type' => 'listing',
            'source_id' => $listing->id,
            'detected_pattern' => 'phone',
            'matched_text' => '08160563589',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListListings::class)->assertSuccessful();

        // Prove the enum-formatting fix actually works, not just "no crash":
        // the badge/text columns and infolist entries need to render the
        // enum's `.value`, not throw trying to string-cast the enum object.
        Livewire::test(ViewListing::class, ['record' => $listing->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('phone')
            ->assertSee('08160563589');

        Livewire::test(CreateListing::class)->assertSuccessful();
        Livewire::test(ListCategories::class)->assertSuccessful();

        $listing->forceDelete();
        $admin->forceDelete();
    }
}
