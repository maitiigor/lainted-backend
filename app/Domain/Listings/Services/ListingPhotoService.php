<?php

namespace App\Domain\Listings\Services;

use App\Domain\Listings\Exceptions\ListingException;
use App\Domain\Listings\Models\Listing;
use App\Domain\Listings\Models\ListingPhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\Font;

class ListingPhotoService
{
    private const MAX_PHOTOS = 6;

    public function upload(Listing $listing, UploadedFile $file): ListingPhoto
    {
        $existingCount = $listing->photos()->count();

        if ($existingCount >= self::MAX_PHOTOS) {
            throw new ListingException('A listing can have at most '.self::MAX_PHOTOS.' photos.');
        }

        $originalPath = $file->store('listings/originals', 'public');
        $watermarkedPath = $this->watermark($originalPath);

        return $listing->photos()->create([
            'original_path' => $originalPath,
            'watermarked_path' => $watermarkedPath,
            'position' => $existingCount,
        ]);
    }

    /**
     * Shift remaining photos down one position each, in ascending order,
     * inside a transaction - the `unique(listing_id, position)` constraint
     * means an out-of-order bulk update can collide mid-transaction
     * (decrementing position 3 to 2 before position 2's own row has moved
     * away from 2 first).
     */
    public function delete(ListingPhoto $photo): void
    {
        $listing = $photo->listing;
        $deletedPosition = $photo->position;

        DB::transaction(function () use ($photo, $listing, $deletedPosition) {
            Storage::disk('public')->delete([$photo->original_path, $photo->watermarked_path]);
            $photo->delete();

            $listing->photos()
                ->where('position', '>', $deletedPosition)
                ->orderBy('position')
                ->get()
                ->each(fn (ListingPhoto $p) => $p->update(['position' => $p->position - 1]));
        });
    }

    /**
     * Downscale (the doc's own "data-light, low-end Android" principle -
     * don't store/serve raw camera-resolution originals) then overlay the
     * brand name, bottom-right, semi-legible over any photo.
     */
    private function watermark(string $originalPath): string
    {
        $manager = ImageManager::usingDriver(GdDriver::class);
        $image = $manager->decodePath(Storage::disk('public')->path($originalPath));

        $image->scaleDown(width: 1600);

        $font = new Font(
            filepath: resource_path('fonts/DMSans-Regular.ttf'),
            size: max(16, (int) round($image->width() * 0.035)),
            color: 'ffffff',
            strokeColor: '000000',
            strokeWidth: 1,
            alignmentHorizontal: Alignment::RIGHT,
            alignmentVertical: Alignment::BOTTOM,
        );

        // Proportional, not a flat 16px - the client renders every photo
        // inside a rounded-corner clip (ListingCard, the listing detail
        // carousel, etc.), and a fixed pixel margin on a 1600px image
        // reads as "right at the edge" regardless of corner radius,
        // getting clipped away. Scales with resolution like the font
        // size above so it stays clear of typical UI rounding at any
        // display size.
        $margin = max(24, (int) round($image->width() * 0.03));
        $image->text('Lainted', $image->width() - $margin, $image->height() - $margin, $font);

        $watermarkedPath = preg_replace('#^listings/originals/#', 'listings/watermarked/', $originalPath);
        $encoded = $image->encodeUsingFormat(Format::JPEG, quality: 65);

        Storage::disk('public')->put($watermarkedPath, (string) $encoded);

        return $watermarkedPath;
    }
}
