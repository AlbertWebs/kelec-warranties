<?php

namespace App\Services;

use App\Models\WarrantyClaim;
use Illuminate\Http\UploadedFile;

class ClaimPhotoService
{
    public const MAX_PHOTOS = 8;

    public const MAX_KILOBYTES = 5120;

    public function __construct(
        protected DocumentStorageService $documentStorage,
    ) {}

    /**
     * @return array<string, list<string>>
     */
    public static function validationRules(): array
    {
        return [
            'photos' => ['nullable', 'array', 'max:'.self::MAX_PHOTOS],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAX_KILOBYTES],
        ];
    }

    /**
     * @param  array<int, UploadedFile|null>|null  $files
     */
    public function storeMany(WarrantyClaim $claim, ?array $files): void
    {
        foreach ($files ?? [] as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $stored = $this->documentStorage->storeReceipt($file, 'claim-photos');

            $claim->photos()->create($stored);
        }
    }
}
