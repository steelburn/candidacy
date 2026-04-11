<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CvFile;
use Illuminate\Support\Facades\Storage;

echo "Populating missing CV data...\n";

$files = CvFile::all();

foreach ($files as $cvFile) {
    if (!$cvFile->mime_type || !$cvFile->original_filename || !$cvFile->stored_filename) {
        echo "Processing ID: {$cvFile->id} (Path: {$cvFile->file_path})\n";

        $path = $cvFile->file_path;
        if (Storage::disk('public')->exists($path)) {
            $absolutePath = Storage::disk('public')->path($path);
            $mimeType = mime_content_type($absolutePath);
            $size = filesize($absolutePath);

            // Extract filename logic
            $storedFilename = basename($path);
            // Default original filename to stored filename if unknown
            $originalFilename = $cvFile->file_name ?? $storedFilename;

            // Update
            $cvFile->mime_type = $mimeType;
            $cvFile->stored_filename = $storedFilename;
            $cvFile->original_filename = $originalFilename;
            $cvFile->file_size = $size;
            $cvFile->save();

            echo "  Updated -> MIME: $mimeType, Size: $size\n";
        }
        else {
            echo "  File not found on disk!\n";
        }
    }
}

echo "Done.\n";