<?php

namespace Tests\Unit\Services;

use App\Services\ExifExtractionService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ExifExtractionServiceTest extends TestCase
{
    private ExifExtractionService $service;
    private string $testImagePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExifExtractionService();
        $this->testImagePath = __DIR__ . '/../../fixtures/test_image.jpg';
    }

    /**
     * Test that delineation values are correctly identified
     * This is critical as stack boundaries depend on these values
     */
    public function test_identifies_delineation_values_correctly(): void
    {
        // Test flash delineation value
        $this->assertTrue(
            $this->service->isDelineationValue('flash', 'Off, Did not fire'),
            'Failed to identify flash delineation value'
        );
        $this->assertFalse(
            $this->service->isDelineationValue('flash', 'On, Return detected'),
            'Incorrectly identified non-delineation flash value'
        );

        // Test exposure mode delineation value
        $this->assertTrue(
            $this->service->isDelineationValue('exposure_mode', 'Auto'),
            'Failed to identify exposure mode delineation value'
        );
        $this->assertFalse(
            $this->service->isDelineationValue('exposure_mode', 'Manual'),
            'Incorrectly identified non-delineation exposure mode value'
        );

        // Test white balance delineation value
        $this->assertTrue(
            $this->service->isDelineationValue('white_balance', 'Auto'),
            'Failed to identify white balance delineation value'
        );
        $this->assertFalse(
            $this->service->isDelineationValue('white_balance', 'Manual'),
            'Incorrectly identified non-delineation white balance value'
        );

        // Test ISO delineation value
        $this->assertTrue(
            $this->service->isDelineationValue('iso', 400),
            'Failed to identify ISO delineation value'
        );
        $this->assertFalse(
            $this->service->isDelineationValue('iso', 200),
            'Incorrectly identified non-delineation ISO value'
        );
    }

    /**
     * Test that flash values are normalized correctly
     * This ensures consistent delineation detection regardless of camera format
     */
    public function test_normalizes_flash_values_correctly(): void
    {
        $exif = [
            'Flash' => 16, // Common value for "Off, Did not fire"
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals('Off, Did not fire', $result['flash']);

        $exif = [
            'Flash' => 1, // Common value for "On, Return detected"
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals('On, Return detected', $result['flash']);
    }

    /**
     * Test that exposure mode values are normalized correctly
     * This ensures consistent delineation detection across different cameras
     */
    public function test_normalizes_exposure_mode_values_correctly(): void
    {
        $exif = [
            'ExposureProgram' => 2, // Program AE (Auto)
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals('Auto', $result['exposure_mode']);

        $exif = [
            'ExposureProgram' => 1, // Manual
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals('Manual', $result['exposure_mode']);
    }

    /**
     * Test that white balance values are normalized correctly
     * This ensures consistent delineation detection across different cameras
     */
    public function test_normalizes_white_balance_values_correctly(): void
    {
        $exif = [
            'WhiteBalance' => 0, // Auto
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals('Auto', $result['white_balance']);

        $exif = [
            'WhiteBalance' => 1, // Manual
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals('Manual', $result['white_balance']);
    }

    /**
     * Test that ISO values are normalized correctly
     * This ensures consistent delineation detection across different cameras
     */
    public function test_normalizes_iso_values_correctly(): void
    {
        $exif = [
            'ISOSpeedRatings' => 400, // Delineation value
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals(400, $result['iso']);

        $exif = [
            'ISOSpeedRatings' => 200, // Non-delineation value
        ];

        $result = $this->service->extract($this->testImagePath);
        $this->assertEquals(200, $result['iso']);
    }

    /**
     * Test that all required delineation fields are validated
     * This ensures we can reliably detect stack boundaries
     */
    public function test_validates_required_delineation_fields(): void
    {
        $validMetadata = [
            'flash' => 'Off, Did not fire',
            'exposure_mode' => 'Auto',
            'white_balance' => 'Auto',
            'iso' => 400
        ];

        $this->assertTrue(
            $this->service->validateDelineationFields($validMetadata),
            'Failed to validate complete metadata'
        );

        $invalidMetadata = [
            'flash' => 'Off, Did not fire',
            'exposure_mode' => 'Auto',
            // Missing white_balance
            'iso' => 400
        ];

        $this->assertFalse(
            $this->service->validateDelineationFields($invalidMetadata),
            'Failed to detect missing delineation field'
        );
    }

    /**
     * Test that unknown values are handled correctly
     * This ensures we don't create invalid stacks with incomplete data
     */
    public function test_handles_unknown_values_correctly(): void
    {
        $metadata = [
            'flash' => 'Unknown',
            'exposure_mode' => 'Auto',
            'white_balance' => 'Auto',
            'iso' => 400
        ];

        $this->assertFalse(
            $this->service->validateDelineationFields($metadata),
            'Failed to detect unknown value in delineation field'
        );
    }

    /**
     * Test that invalid ISO values are handled correctly
     * This ensures we don't create invalid stacks with bad ISO data
     */
    public function test_handles_invalid_iso_values_correctly(): void
    {
        $metadata = [
            'flash' => 'Off, Did not fire',
            'exposure_mode' => 'Auto',
            'white_balance' => 'Auto',
            'iso' => 0 // Invalid ISO value
        ];

        $this->assertFalse(
            $this->service->validateDelineationFields($metadata),
            'Failed to detect invalid ISO value'
        );
    }
}
