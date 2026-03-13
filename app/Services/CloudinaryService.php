<?php

namespace App\Services;

class CloudinaryService
{
    /**
     * Return the Cloudinary config needed for client-side (unsigned) uploads.
     */
    public function getConfig(): array
    {
        return [
            'cloudName'    => config('services.cloudinary.cloud_name'),
            'uploadPreset' => config('services.cloudinary.upload_preset'),
        ];
    }
}
