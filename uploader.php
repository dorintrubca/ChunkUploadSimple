<?php

class Uploader
{
    private string $uploadDir;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * @throws Exception
     */
    public function handle(array $post, array $files): void
    {
        $uploadId    = $post['uploadId']     ?? null;
        $chunkIndex  = $post['chunkIndex']   ?? null;
        $totalChunks = $post['totalChunks']  ?? null;
        $fileName    = $post['fileName']     ?? null;
        $fileChunk   = $files['chunk']       ?? null;

        if (!$uploadId || $chunkIndex === null || !$fileName || !$totalChunks || !$fileChunk) {
            throw new Exception('Missing required parameters.');
        }

        if ($fileChunk['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error during chunk upload.');
        }

        $tempDir = $this->uploadDir . $uploadId . '/';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $chunkPath = $tempDir . 'chunk_' . $chunkIndex;

        if (!move_uploaded_file($fileChunk['tmp_name'], $chunkPath)) {
            throw new Exception("Failed to save chunk $chunkIndex.");
        }

        $this->tryAssembleChunks($tempDir, $fileName, $totalChunks);
    }

    /**
     * @throws Exception
     */
    private function tryAssembleChunks(string $tempDir, string $fileName, int $totalChunks): void
    {
        $chunks = glob($tempDir . 'chunk_*');

        if (count($chunks) < $totalChunks) {
            return;
        }

        $finalPath = $this->uploadDir . basename($fileName);
        $output = fopen($finalPath, 'wb');

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFile = $tempDir . 'chunk_' . $i;
            if (!file_exists($chunkFile)) {
                throw new Exception("Missing chunk $i during assembly.");
            }

            $input = fopen($chunkFile, 'rb');
            stream_copy_to_stream($input, $output);
            fclose($input);
        }

        fclose($output);

        array_map('unlink', glob($tempDir . 'chunk_*'));
        rmdir($tempDir);
    }
}
