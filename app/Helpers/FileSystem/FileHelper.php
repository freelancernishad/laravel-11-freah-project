<?php

use Illuminate\Support\Facades\Storage;




/**
     * Upload a file to the S3 disk.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $directory
     * @return string
     * @throws \Exception
     */
    function uploadFileToS3($file, $directory = 'uploads')
    {
        // Validate the file
        if (!$file->isValid()) {
            throw new \Exception('Invalid file upload');
        }

        // Generate a unique file name (optional, based on timestamp or any naming convention)
        $fileName = time() . '_' . $file->getClientOriginalName();

        // Store the file in the 's3' disk under the specified directory
        $filePath = $file->storeAs($directory, $fileName, 's3');

        // Return the file path on the S3 disk
        return $filePath;
    }


/**
 * Upload a file to the 'protected' disk.
 *
 * @param \Illuminate\Http\UploadedFile $file
 * @param string $directory
 * @return string $filePath
 */
function uploadFileToProtected($file, $directory = 'uploads')
{
    // Validate file
    if (!$file->isValid()) {
        throw new \Exception('Invalid file upload');
    }

    // Store file in the 'protected' disk
    $filePath = $file->store($directory, 'protected');

    return $filePath;
}

/**
 * Read a file from the 'protected' disk.
 *
 * @param string $filename
 * @return \Symfony\Component\HttpFoundation\StreamedResponse
 */
function readFileFromProtected($filename)
{
    // Define file path
    $filePath = "uploads/{$filename}";

    // Check if the file exists
    if (!Storage::disk('protected')->exists($filePath)) {
        throw new \Exception('File not found');
    }

    // Return file as download
    return Storage::disk('protected')->download($filePath);
}
