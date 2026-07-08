<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileUploadService
{
    /**
     * Allowed MIME types for images (whitelist approach for security)
     */
    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    /**
     * Allowed file extensions for images
     */
    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    /**
     * Allowed MIME types for documents
     */
    private const ALLOWED_DOCUMENT_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /**
     * Allowed file extensions for documents
     */
    private const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

    /**
     * Maximum file size in kilobytes (default 5MB)
     */
    private int $maxSize;

    public function __construct()
    {
        $this->maxSize = (int) config('filesystems.max_upload_size', 5120); // 5MB default
    }

    /**
     * Validate and store an uploaded image file
     *
     * @param UploadedFile $file
     * @param string $directory The storage directory (e.g., 'products', 'users')
     * @param string|null $oldPath Previous file path to delete if replacing
     * @return string The stored file path
     * @throws RuntimeException
     */
    public function storeImage(UploadedFile $file, string $directory = 'images', ?string $oldPath = null): string
    {
        $this->validateImage($file);

        // Delete old file if exists
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Generate safe filename with timestamp to prevent collisions
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '_' . time() . '.' . $extension;
        $path = $directory . '/' . date('Y/m');

        // Store file
        $storedPath = $file->storeAs($path, $filename, 'public');

        if (!$storedPath) {
            throw new RuntimeException('Failed to store uploaded file.');
        }

        return $storedPath;
    }

    /**
     * Validate and store an uploaded document file
     *
     * @param UploadedFile $file
     * @param string $directory The storage directory
     * @param string|null $oldPath Previous file path to delete if replacing
     * @return string The stored file path
     * @throws RuntimeException
     */
    public function storeDocument(UploadedFile $file, string $directory = 'documents', ?string $oldPath = null): string
    {
        $this->validateDocument($file);

        // Delete old file if exists
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Generate safe filename
        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '_' . time() . '.' . $extension;
        $path = $directory . '/' . date('Y/m');

        // Store file
        $storedPath = $file->storeAs($path, $filename, 'public');

        if (!$storedPath) {
            throw new RuntimeException('Failed to store uploaded file.');
        }

        return $storedPath;
    }

    /**
     * Validate an image file
     *
     * @param UploadedFile $file
     * @throws RuntimeException
     */
    private function validateImage(UploadedFile $file): void
    {
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            throw new RuntimeException('File upload failed. Please try again.');
        }

        // Check file size
        if ($file->getSize() > $this->maxSize * 1024) {
            $maxSizeMB = round($this->maxSize / 1024, 2);
            throw new RuntimeException("File size exceeds maximum allowed size of {$maxSizeMB}MB.");
        }

        // Check MIME type (server-detected, primary security check)
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_IMAGE_MIMES, true)) {
            throw new RuntimeException('Invalid file type. Only JPG, PNG, and WebP images are allowed.');
        }

        // Check file extension (client-provided, additional layer)
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            throw new RuntimeException('Invalid file extension. Only .jpg, .jpeg, .png, and .webp are allowed.');
        }

        // Verify it's actually an image by reading the file header (prevents disguised files)
        $imageInfo = @getimagesize($file->getRealPath());
        if ($imageInfo === false) {
            throw new RuntimeException('File is not a valid image.');
        }

        // Additional security: check image dimensions (prevent zip bombs and excessive memory usage)
        [$width, $height] = $imageInfo;
        $maxPixels = 50000000; // 50 megapixels max
        if ($width * $height > $maxPixels) {
            throw new RuntimeException('Image dimensions exceed maximum allowed size.');
        }

        // Prevent images that are too small (likely suspicious)
        if ($width < 10 || $height < 10) {
            throw new RuntimeException('Image dimensions are too small. Minimum 10x10 pixels required.');
        }
    }

    /**
     * Validate a document file
     *
     * @param UploadedFile $file
     * @throws RuntimeException
     */
    private function validateDocument(UploadedFile $file): void
    {
        // Check if file was uploaded successfully
        if (!$file->isValid()) {
            throw new RuntimeException('File upload failed. Please try again.');
        }

        // Check file size
        if ($file->getSize() > $this->maxSize * 1024) {
            $maxSizeMB = round($this->maxSize / 1024, 2);
            throw new RuntimeException("File size exceeds maximum allowed size of {$maxSizeMB}MB.");
        }

        // Check MIME type (server-detected, primary security check)
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_DOCUMENT_MIMES, true)) {
            throw new RuntimeException('Invalid file type. Only PDF, Word, and Excel documents are allowed.');
        }

        // Check file extension (client-provided, additional layer)
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_DOCUMENT_EXTENSIONS, true)) {
            throw new RuntimeException('Invalid file extension. Only .pdf, .doc, .docx, .xls, and .xlsx are allowed.');
        }

        // Prevent zero-byte files
        if ($file->getSize() === 0) {
            throw new RuntimeException('Document file is empty.');
        }
    }

    /**
     * Delete a file from storage
     *
     * @param string $path
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Get allowed image extensions
     *
     * @return array
     */
    public static function getAllowedImageExtensions(): array
    {
        return self::ALLOWED_IMAGE_EXTENSIONS;
    }

    /**
     * Get allowed document extensions
     *
     * @return array
     */
    public static function getAllowedDocumentExtensions(): array
    {
        return self::ALLOWED_DOCUMENT_EXTENSIONS;
    }
}
