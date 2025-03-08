<?php

namespace App\Http\Controllers;

use App\Services\ExifExtractionService;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles file uploads and metadata extraction
 *
 * @property-read ExifExtractionService $exifService Service for EXIF metadata extraction
 */
class UploadController extends Controller
{
    /** @readonly */
    private ExifExtractionService $exifService;

    public function __construct(ExifExtractionService $exifService)
    {
        $this->exifService = $exifService;
    }

    /**
     * Show the upload form
     */
    public function index()
    {
        return view('upload');
    }

    /**
     * Handle file upload and metadata extraction
     */
    public function store(Request $request): StreamedResponse
    {
        // Validate request
        $request->validate([
            'files.*' => 'required|file|mimes:jpeg,jpg|max:8192', // 8MB limit
            'batch_id' => 'nullable|uuid',
            'tag' => 'nullable|string|max:50'
        ]);

        // Start streaming response
        return response()->stream(function () use ($request) {
            // Disable output buffering
            if (ob_get_level()) ob_end_clean();

            $this->streamLine('Starting upload process...');

            try {
                DB::beginTransaction();

                // Get or create batch
                $batchId = $request->input('batch_id') ?? Str::uuid();
                $batch = DB::table('flambient_batches')->find($batchId);

                if (!$batch) {
                    DB::table('flambient_batches')->insert([
                        'id' => $batchId,
                        'status' => 'uploading',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'expires_at' => now()->addHours(72)
                    ]);
                    $this->streamLine("Created new batch: $batchId");
                }

                // Process each file
                foreach ($request->file('files') as $index => $file) {
                    $fileNumber = $index + 1;
                    $fileName = $file->getClientOriginalName();
                    $this->streamLine("Processing file $fileNumber: $fileName");

                    // Store file
                    $path = $file->store("uploads/$batchId", 'local');
                    $fullPath = Storage::path($path);

                    // Extract EXIF data
                    $result = $this->exifService->extract($fullPath);

                    if (!$result['success']) {
                        $this->streamLine("Warning: {$result['error']}", 'warning');
                        continue;
                    }

                    // Save image record
                    $imageId = Str::uuid();
                    DB::table('flambient_images')->insert([
                        'id' => $imageId,
                        'batch_id' => $batchId,
                        'filename' => $fileName,
                        'file_size' => $file->getSize(),
                        'storage_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Extract metadata fields
                    DB::table('flambient_image_metadata')->insert([
                        'image_id' => $imageId,
                        'flash_status' => $result['metadata']['flash'],
                        'exposure_mode' => $result['metadata']['exposure_mode'],
                        'white_balance' => $result['metadata']['white_balance'],
                        'iso' => $result['metadata']['iso'],
                        'exposure_time' => $result['metadata']['exposure_time'],
                        'aperture' => $result['metadata']['aperture'],
                        'tag' => $request->input('tag'),
                        'ignore' => false, // Default to false
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // Update batch stats
                    DB::table('flambient_batches')
                        ->where('id', $batchId)
                        ->increment('image_count');

                    DB::table('flambient_batches')
                        ->where('id', $batchId)
                        ->increment('total_size', $file->getSize());

                    $this->streamLine("Successfully processed $fileName", 'success');
                }

                // Update batch status
                DB::table('flambient_batches')
                    ->where('id', $batchId)
                    ->update([
                        'status' => 'uploaded',
                        'updated_at' => now()
                    ]);

                DB::commit();
                $this->streamLine("Upload complete! Batch ID: $batchId", 'success');
                $this->streamLine("END_STREAM");

            } catch (Throwable $e) {
                DB::rollBack();
                $this->streamLine("Error: {$e->getMessage()}", 'error');
                $this->streamLine("END_STREAM");
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no'
        ]);
    }

    /**
     * Stream a line of text to the client
     */
    private function streamLine(string $message, string $type = 'info'): void
    {
        $timestamp = now()->format('H:i:s');
        $data = json_encode([
            'timestamp' => $timestamp,
            'message' => $message,
            'type' => $type
        ]);

        echo "data: $data\n\n";
        flush();
    }
}
