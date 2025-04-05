<?php

require_once 'uploader.php';

try {
    $uploader = new Uploader(__DIR__ . '/uploads');
    $uploader->handle($_POST, $_FILES);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}
