<?php

namespace App\Services;

use ImageKit\ImageKit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageKitService
{
    private $imageKit;

    public function __construct()
    {
        $this->imageKit = new ImageKit(
            config('services.imagekit.public_key'),
            config('services.imagekit.private_key'),
            config('services.imagekit.url_endpoint')
        );
    }

    public function uploadImage(UploadedFile $file, string $folder = 'pengaduan'): ?string
    {
        try {
            // Generate unique filename
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Read file content
            $fileContent = file_get_contents($file->getRealPath());
            
            // Upload to ImageKit
            $response = $this->imageKit->uploadFile([
                'file' => base64_encode($fileContent), // base64 encoded file content
                'fileName' => $fileName,
                'folder' => '/' . $folder, // folder in ImageKit
                'useUniqueFileName' => true,
            ]);

            // Return URL dari ImageKit
            return $response->result->url ?? null;

        } catch (\Exception $e) {
            Log::error('ImageKit upload failed: ' . $e->getMessage());
            return null;
        }
    }

    public function deleteImage(string $fileId): bool
    {
        try {
            $this->imageKit->deleteFile($fileId);
            return true;
        } catch (\Exception $e) {
            Log::error('ImageKit delete failed: ' . $e->getMessage());
            return false;
        }
    }
} 