<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImageResizeService
{
    private int $maxWidth = 1200;
    private int $maxHeight = 800;
    private int $quality = 85;

    /**
     * Resize and optimize an uploaded image
     * 
     * @param UploadedFile $uploadedFile The uploaded image file
     * @param string $uploadDir The directory to save the resized image
     * @return string The filename of the resized image
     * @throws \Exception If image processing fails
     */
    public function resizeAndSave(UploadedFile $uploadedFile, string $uploadDir): string
    {
        // Generate unique filename
        $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
        $extension = $uploadedFile->guessExtension();
        $newFilename = $safeFilename . '_' . uniqid() . '.' . $extension;
        
        // Create upload directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Load and resize image
        $imagePath = $uploadedFile->getRealPath();
        
        // Detect image type
        $imageInfo = @getimagesize($imagePath);
        if ($imageInfo === false) {
            throw new \Exception('Invalid image file');
        }

        $mimeType = $imageInfo['mime'];
        
        // Create image resource
        $image = null;
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($imagePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = imagecreatefromwebp($imagePath);
                } else {
                    throw new \Exception('WebP support not available on this server');
                }
                break;
            default:
                throw new \Exception('Unsupported image type: ' . $mimeType);
        }

        if ($image === false) {
            throw new \Exception('Failed to create image resource');
        }

        // Calculate new dimensions (maintain aspect ratio)
        $currentWidth = imagesx($image);
        $currentHeight = imagesy($image);
        
        $ratio = min($this->maxWidth / $currentWidth, $this->maxHeight / $currentHeight);
        
        if ($ratio < 1) {
            // Image is larger than max dimensions, resize it
            $newWidth = (int)($currentWidth * $ratio);
            $newHeight = (int)($currentHeight * $ratio);
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency for PNG
            if ($mimeType === 'image/png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $currentWidth, $currentHeight);
            imagedestroy($image);
            $image = $resized;
        }

        // Save resized image
        $filepath = $uploadDir . '/' . $newFilename;
        
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $filepath, $this->quality);
                break;
            case 'image/png':
                imagepng($image, $filepath, 9);
                break;
            case 'image/gif':
                imagegif($image, $filepath);
                break;
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    imagewebp($image, $filepath, $this->quality);
                } else {
                    throw new \Exception('WebP support not available on this server');
                }
                break;
        }

        imagedestroy($image);

        return $newFilename;
    }

    /**
     * Set maximum width for resizing
     */
    public function setMaxWidth(int $width): self
    {
        $this->maxWidth = $width;
        return $this;
    }

    /**
     * Set maximum height for resizing
     */
    public function setMaxHeight(int $height): self
    {
        $this->maxHeight = $height;
        return $this;
    }

    /**
     * Set JPEG quality (1-100)
     */
    public function setQuality(int $quality): self
    {
        $this->quality = max(1, min(100, $quality));
        return $this;
    }
}
