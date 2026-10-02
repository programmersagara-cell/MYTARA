<?php
/**
 * File Upload Helper
 */

namespace App\Helpers;

class File
{
    private string $uploadPath;
    private array $allowedTypes;
    private int $maxSize;

    public function __construct()
    {
        $appConfig = require CONFIG_PATH . '/app.php';
        $uploadConfig = $appConfig['uploads'];
        
        $this->uploadPath = $uploadConfig['path'];
        $this->allowedTypes = $uploadConfig['allowed_types'];
        $this->maxSize = $uploadConfig['max_size'];
    }

    /**
     * Upload a file
     */
    public function upload(array $file, string $subdirectory = ''): array
    {
        // Validate file
        $validation = Security::validateFileUpload($file, $this->allowedTypes, $this->maxSize);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        // Sanitize filename
        $originalName = Security::sanitizeFilename(pathinfo($file['name'], PATHINFO_FILENAME));
        $extension = $validation['extension'];
        
        // Generate unique filename
        $filename = $originalName . '_' . time() . '.' . $extension;

        // Build target path
        $targetPath = $this->uploadPath;
        if ($subdirectory) {
            $targetPath .= '/' . trim($subdirectory, '/');
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0775, true);
            }
        }
        $targetPath .= '/' . $filename;

        // Move file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Set proper permissions
            chmod($targetPath, 0644);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => str_replace($this->uploadPath . '/', '', $targetPath),
                'size' => $file['size'],
                'mime' => mime_content_type($targetPath),
            ];
        }

        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->uploadPath . '/' . ltrim($path, '/');
        
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }

        return false;
    }

    /**
     * Get file URL
     */
    public function getUrl(string $path): string
    {
        $baseUrl = rtrim(BASE_URL, '/');
        return $baseUrl . '/public/uploads/' . ltrim($path, '/');
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return file_exists($this->uploadPath . '/' . ltrim($path, '/'));
    }

    /**
     * Get file size
     */
    public function getSize(string $path): ?int
    {
        $fullPath = $this->uploadPath . '/' . ltrim($path, '/');
        return file_exists($fullPath) ? filesize($fullPath) : null;
    }
}

