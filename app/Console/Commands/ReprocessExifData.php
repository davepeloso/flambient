<?php

namespace App\Console\Commands;

use App\Models\FlambientImage;
use App\Services\ExifExtractionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReprocessExifData extends Command
{
    protected $signature = 'flambient:reprocess-exif';
    protected $description = 'Reprocess EXIF data for all images';

    public function handle(): void
    {
        $images = FlambientImage::with('metadata')->get();
        $bar = $this->output->createProgressBar(count($images));
        $errors = 0;
        $exifService = new ExifExtractionService();

        foreach ($images as $image) {
            try {
                $filePath = Storage::path($image->storage_path);
                $result = $exifService->extract($filePath);
                
                if (!$result['success']) {
                    $this->error(sprintf(
                        "\nError extracting EXIF from %s: %s",
                        $image->filename,
                        $result['error']
                    ));
                    $errors++;
                    continue;
                }

                // Update or create metadata
                $image->metadata()->updateOrCreate(
                    ['image_id' => $image->id],
                    $result['metadata']
                );

                $this->info(sprintf(
                    "\nImage: %s, Flash: %s",
                    $image->filename,
                    $result['metadata']['flash']
                ));

            } catch (\Throwable $e) {
                $this->error(sprintf(
                    "\nError processing image %s: %s",
                    $image->filename,
                    $e->getMessage()
                ));
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info(sprintf(
            "Processed %d images with %d errors",
            count($images),
            $errors
        ));
    }
}
