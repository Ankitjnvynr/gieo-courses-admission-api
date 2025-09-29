<?php
class FileUpload {
    private $uploadDir;
    private $maxFileSize;
    private $allowedTypes;

    public function __construct() {
        $this->uploadDir = $_ENV['UPLOAD_DIR'];
        $this->maxFileSize = $_ENV['MAX_FILE_SIZE'];
        $this->allowedTypes = explode(',', $_ENV['ALLOWED_IMAGE_TYPES']);
        
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    public function upload($file, $subfolder = '') {
        try {
            if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
                throw new Exception("No file uploaded");
            }

            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload error: " . $file['error']);
            }

            if ($file['size'] > $this->maxFileSize) {
                throw new Exception("File size exceeds maximum allowed size");
            }

            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $this->allowedTypes)) {
                throw new Exception("Invalid file type. Allowed types: " . implode(', ', $this->allowedTypes));
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!in_array($mimeType, $allowedMimes)) {
                throw new Exception("Invalid file MIME type");
            }

            $targetDir = $this->uploadDir . ($subfolder ? $subfolder . '/' : '');
            if (!file_exists($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
            $targetPath = $targetDir . $uniqueName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Failed to move uploaded file");
            }

            return [
                'success' => true,
                'filename' => $uniqueName,
                'path' => $targetPath,
                'url' => $_ENV['BASE_URL'] . '/' . $targetPath
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function deleteFile($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    public function validateImage($file) {
        $check = getimagesize($file['tmp_name']);
        if ($check === false) {
            return false;
        }
        return true;
    }
}
?>