<?php

namespace App\Services;

use RuntimeException;
use PHPExif\Exif;
use PHPExif\Reader\Reader;
use Illuminate\Support\Facades\Log;

class ExifExtractionService
{
    /**
     * Delineation field values that indicate a transition point.
     * These values are metadata only and make no assumptions about actual lighting conditions.
     */
    private const DELINEATION_VALUES = [
        'flash' => 'Off, Did not fire',
        'exposure_mode' => 'Auto',
        'white_balance' => 'Auto',
        'iso' => 400
    ];

    /**
     * Required fields for delineation detection
     */
    private array $requiredFields = [
        'flash',
        'exposure_mode',
        'white_balance',
        'iso'
    ];

    private Reader $reader;

    public function __construct()
    {
        $this->reader = Reader::factory(Reader::TYPE_NATIVE);
    }

    /**
     * Extract metadata focusing on delineation fields
     * 
     * @param string $filePath Path to the image file
     * @return array Extracted and normalized metadata
     * @throws RuntimeException If EXIF data cannot be read
     */
    public function extract(string $filePath): array
    {
        try {
            // Try native EXIF reading first
            $exif = @exif_read_data($filePath);
            
            if ($exif === false) {
                // Fallback to php-exif library
                $exif = $this->reader->read($filePath);
                if (!$exif instanceof Exif) {
                    throw new RuntimeException("Failed to read EXIF data");
                }
                $exif = $this->normalizePhpExif($exif);
            }

            return $this->extractDelineationFields($exif);
        } catch (\Exception $e) {
            Log::error('EXIF extraction failed', [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException("Failed to extract EXIF data: {$e->getMessage()}");
        }
    }

    /**
     * Extract and normalize only the fields needed for delineation detection
     */
    private function extractDelineationFields(array $exif): array
    {
        return [
            'flash' => $this->extractFlash($exif),
            'exposure_mode' => $this->extractExposureMode($exif),
            'white_balance' => $this->extractWhiteBalance($exif),
            'iso' => $this->extractIso($exif)
        ];
    }

    /**
     * Extract and normalize flash value
     * This is purely metadata and makes no assumptions about actual flash firing
     */
    private function extractFlash(array $exif): string
    {
        $flash = $exif['Flash'] ?? null;
        
        // Handle different flash value formats
        if (is_numeric($flash)) {
            // Convert numeric flash values to string representation
            return match((int)$flash) {
                0, 16, 24, 32 => 'Off, Did not fire',
                1, 5, 7, 9, 13, 15, 25, 29, 31, 65, 69, 71, 73, 77, 79, 89, 93, 95 => 'On, Return detected',
                default => 'Unknown'
            };
        }
        
        return $flash ?? 'Unknown';
    }

    /**
     * Extract and normalize exposure mode
     * Focuses on the delineation value 'Auto' without making lighting assumptions
     */
    private function extractExposureMode(array $exif): string
    {
        $mode = $exif['ExposureProgram'] ?? $exif['ExposureMode'] ?? null;
        
        if (is_numeric($mode)) {
            return match((int)$mode) {
                2 => 'Auto',
                1, 3, 4 => 'Manual',
                default => 'Unknown'
            };
        }
        
        return $mode ?? 'Unknown';
    }

    /**
     * Extract and normalize white balance
     * Focuses on the delineation value 'Auto' without making lighting assumptions
     */
    private function extractWhiteBalance(array $exif): string
    {
        $wb = $exif['WhiteBalance'] ?? null;
        
        if (is_numeric($wb)) {
            return match((int)$wb) {
                0 => 'Auto',
                1 => 'Manual',
                default => 'Unknown'
            };
        }
        
        return $wb ?? 'Unknown';
    }

    /**
     * Extract and normalize ISO value
     * Focuses on the delineation value 400 without making lighting assumptions
     */
    private function extractIso(array $exif): int
    {
        $iso = $exif['ISOSpeedRatings'] ?? null;
        
        if (is_array($iso)) {
            $iso = $iso[0] ?? null;
        }
        
        return (int)$iso ?: 0;
    }

    /**
     * Normalize data from php-exif library to match native EXIF format
     */
    private function normalizePhpExif(Exif $exif): array
    {
        return [
            'Flash' => $exif->getFlash(),
            'ExposureProgram' => $exif->getExposureMode(),
            'WhiteBalance' => $exif->getWhiteBalance(),
            'ISOSpeedRatings' => $exif->getIso()
        ];
    }

    /**
     * Check if a field value matches the delineation value
     * This is used to detect transition points without making lighting assumptions
     */
    public function isDelineationValue(string $field, $value): bool
    {
        if (!isset(self::DELINEATION_VALUES[$field])) {
            return false;
        }

        if ($field === 'iso') {
            return (int)$value === self::DELINEATION_VALUES[$field];
        }

        return $value === self::DELINEATION_VALUES[$field];
    }

    /**
     * Validate that all required delineation fields are present
     */
    public function validateDelineationFields(array $metadata): bool
    {
        foreach ($this->requiredFields as $field) {
            if (!isset($metadata[$field]) || 
                $metadata[$field] === 'Unknown' || 
                ($field === 'iso' && $metadata[$field] === 0)) {
                return false;
            }
        }
        return true;
    }
}
