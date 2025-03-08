<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ExifExtractionService
{
    /**
     * Required EXIF fields for stacking
     */
    private array $requiredFields = [
        'flash',
        'exposure_mode',
        'white_balance',
        'iso',
        'exposure_time',
        'aperture'
    ];

    /**
     * Extract required EXIF data from an image file
     */
    public function extract(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        try {
            $exif = @exif_read_data($filePath);
            
            if ($exif === false) {
                Log::warning('No EXIF data found', ['file' => $filePath]);
                return [
                    'success' => false,
                    'error' => 'No EXIF data found in image',
                    'metadata' => []
                ];
            }

            $metadata = [
                'flash' => $this->extractFlash($exif),
                'exposure_mode' => $this->extractExposureMode($exif),
                'white_balance' => $this->extractWhiteBalance($exif),
                'iso' => $this->extractIso($exif),
                'exposure_time' => $this->extractExposureTime($exif),
                'aperture' => $this->extractAperture($exif)
            ];

            Log::debug('Successfully extracted EXIF data', [
                'file' => $filePath,
                'metadata' => $metadata
            ]);

            return [
                'success' => true,
                'error' => null,
                'metadata' => $metadata
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to extract EXIF data', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to extract EXIF data: ' . $e->getMessage(),
                'metadata' => []
            ];
        }
    }

    /**
     * Extract and normalize flash value
     */
    private function extractFlash(array $exif): string
    {
        $flash = $exif['Flash'] ?? 0;
        
        // Common values:
        // 0 = No Flash
        // 1 = Fired
        // 9 = Fill Fired
        // 13 = On, Return not detected
        // 16 = Off, Did not fire
        // 24 = Auto, Did not fire
        // 25 = Auto, Fired
        
        return match((int)$flash) {
            0, 16, 24 => 'Off, Did not fire',
            1, 9, 25 => 'On, Return not detected',
            5, 13, 29 => 'On, Return detected',
            default => 'Off, Did not fire'
        };
    }

    /**
     * Extract and normalize exposure mode
     */
    private function extractExposureMode(array $exif): string
    {
        $mode = $exif['ExposureMode'] ?? 0;
        
        return match((int)$mode) {
            0 => 'Auto',
            1 => 'Manual',
            2 => 'Auto bracket',
            default => 'Unknown'
        };
    }

    /**
     * Extract and normalize white balance
     */
    private function extractWhiteBalance(array $exif): string
    {
        $wb = $exif['WhiteBalance'] ?? 0;
        
        return match((int)$wb) {
            0 => 'Auto',
            1 => 'Manual',
            default => 'Unknown'
        };
    }

    /**
     * Extract and normalize ISO value
     */
    private function extractIso(array $exif): ?int
    {
        return isset($exif['ISOSpeedRatings']) ? (int)$exif['ISOSpeedRatings'] : null;
    }

    /**
     * Extract and normalize exposure time
     */
    private function extractExposureTime(array $exif): ?string
    {
        return $exif['ExposureTime'] ?? null;
    }

    /**
     * Extract and normalize aperture value
     */
    private function extractAperture(array $exif): ?string
    {
        return $exif['FNumber'] ?? null;
    }
}
