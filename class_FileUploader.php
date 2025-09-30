<?php
class FileUploader {
    private $upload_dir;

    public function __construct() {
        $this->upload_dir = __DIR__ . '/uploads/hospitals/';  // ⬅️ هذا هو المكان الصحيح لتخزين الملفات
    }

    public function upload($file, $prefix) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $prefix . uniqid() . '.' . $ext;
            $destination = $this->upload_dir . $filename;

            if (!is_dir($this->upload_dir)) {
                mkdir($this->upload_dir, 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                return $filename;
            }
        }
        return false;
    }
}

