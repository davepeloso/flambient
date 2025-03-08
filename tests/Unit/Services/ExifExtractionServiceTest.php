<?php

namespace Tests\Unit\Services;

use App\Services\ExifExtractionService;
use PHPUnit\Framework\TestCase;

class ExifExtractionServiceTest extends TestCase
{
    private ExifExtractionService $service;
    private string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExifExtractionService();
        $this->fixturesPath = __DIR__ . '/../../Fixtures';
        
        // Create fixtures directory if it doesn't exist
        if (!file_exists($this->fixturesPath)) {
            mkdir($this->fixturesPath, 0755, true);
        }
    }

    public function test_extract_returns_error_for_nonexistent_file(): void
    {
        $result = $this->service->extract('/nonexistent/path/image.jpg');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('File not found', $result['error']);
    }

    public function test_extract_accepts_single_delineation_field(): void
    {
        // This test will pass with just ISO data
        $this->markTestSkipped(
            'Need to create test image with only ISO data'
        );

        // Example of expected successful result
        $expectedStructure = [
            'success' => true,
            'metadata' => [
                'delineation_fields' => [
                    'ISO' => [
                        'field' => 'ISO',
                        'value' => 100
                    ]
                ],
                'all_fields' => [
                    'ISO' => 100
                ]
            ]
        ];
    }

    public function test_extract_captures_all_available_fields(): void
    {
        // This test will check that we get all available fields
        $this->markTestSkipped(
            'Need to create test image with multiple EXIF fields'
        );

        // Example of expected fields
        $expectedFields = [
            'ISO',
            'ExposureTime',
            'Flash',
            'FNumber',
            'FocalLength',
            'MeteringMode',
            'ShutterSpeedValue'
        ];
    }

    public function test_extract_formats_exposure_time(): void
    {
        // This test will check exposure time fraction conversion
        $this->markTestSkipped(
            'Need to create test image with fractional exposure time'
        );

        // Example: "1/100" should be converted to 0.01
    }

    public function test_extract_handles_missing_exif_data(): void
    {
        $this->markTestSkipped(
            'Need to create test image without EXIF data'
        );
    }

    /**
     * Helper method to create a test image with specific EXIF data
     * We'll implement this when we set up the fixtures
     */
    private function createTestImage(array $exifData): string
    {
        // TODO: Implement test image creation
        return '';
    }
}
