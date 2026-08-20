<?php

namespace App\Domain\Users\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CloudinaryService
{
    /**
     * Signed upload to Cloudinary via direct HTTP (no SDK/package).
     */
    public function upload(UploadedFile $file, string $folder = 'lainted'): string
    {
        $timestamp = now()->timestamp;

        $paramsToSign = ['folder' => $folder, 'timestamp' => $timestamp];
        ksort($paramsToSign);

        $toSign = collect($paramsToSign)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');

        $signature = sha1($toSign.config('services.cloudinary.api_secret'));

        $response = Http::attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->post(
                'https://api.cloudinary.com/v1_1/'.config('services.cloudinary.cloud_name').'/image/upload',
                [
                    'api_key' => config('services.cloudinary.api_key'),
                    'timestamp' => $timestamp,
                    'folder' => $folder,
                    'signature' => $signature,
                ]
            );

        if (! $response->successful()) {
            throw new RuntimeException('Photo upload failed: '.$response->body());
        }

        return $response->json('secure_url');
    }
}
