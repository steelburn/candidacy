<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$files = \App\Models\CvFile::latest()->take(5)->get();
foreach ($files as $file) {
    echo "ID: {$file->id}\n";
    echo "Filename: {$file->original_filename}\n";
    echo "MIME: {$file->mime_type}\n";
    echo "Path: {$file->file_path}\n";
    echo "-------------------\n";
}