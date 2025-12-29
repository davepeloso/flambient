<?php

namespace App\Services\ImageProcessor\Generators;

use App\Services\ImageProcessor\Contracts\ScriptGeneratorInterface;

/**
 * Flambient Generator - Combines ambient and flash exposures.
 *
 * This generator implements the Flambient technique which blends
 * ambient and flash exposures using luminosity masking for
 * natural-looking real estate photography.
 */
class FlambientGenerator implements ScriptGeneratorInterface
{
    public function getKey(): string
    {
        return 'flambient';
    }

    public function getName(): string
    {
        return 'Flambient (Ambient + Flash Blend)';
    }

    public function getDescription(): string
    {
        return 'Combines ambient and flash exposures using luminosity masking for natural-looking real estate photos. Uses a 5-step algorithm to blend lighting seamlessly.';
    }

    public function getClassificationStrategy(): ?string
    {
        // Uses the Flash EXIF field to distinguish ambient vs flash shots
        return 'flash';
    }

    public function getConfigurationSchema(): array
    {
        return [
            'level_low' => [
                'type' => 'text',
                'label' => 'Level Low (ambient mask threshold)',
                'default' => '40%',
                'description' => 'Black point adjustment for mask generation',
            ],
            'level_high' => [
                'type' => 'text',
                'label' => 'Level High (ambient mask upper bound)',
                'default' => '140%',
                'description' => 'White point adjustment for mask generation',
            ],
            'gamma' => [
                'type' => 'text',
                'label' => 'Gamma (ambient mask correction)',
                'default' => '1.0',
                'description' => 'Gamma correction for midtones in mask',
            ],
            'output_prefix' => [
                'type' => 'text',
                'label' => 'Output file prefix',
                'default' => 'flambient',
                'description' => 'Prefix for output filenames',
            ],
            'enable_darken_export' => [
                'type' => 'confirm',
                'label' => 'Export darkened flash composite?',
                'default' => false,
                'description' => 'Creates an additional _tmp file with darkened flash composite',
            ],
        ];
    }

    public function generateScript(
        array $images,
        array $config,
        string $outputPath
    ): string {
        $ambientFiles = $images['ambient'] ?? [];
        $flashFiles = $images['flash'] ?? [];

        $levelLow = $config['level_low'] ?? '40%';
        $levelHigh = $config['level_high'] ?? '140%';
        $gamma = $config['gamma'] ?? '1.0';

        $numAmbient = count($ambientFiles);
        $numFlash = count($flashFiles);

        $script = [];
        $script[] = "# Flambient Blending Script";
        $script[] = "# Ambient files: " . ($numAmbient > 0 ? implode(', ', array_map('basename', $ambientFiles)) : 'None');
        $script[] = "# Flash files:   " . ($numFlash > 0 ? implode(', ', array_map('basename', $flashFiles)) : 'None');
        $script[] = "";

        // Validate we have both ambient and flash
        if ($numAmbient === 0 || $numFlash === 0) {
            $script[] = "# ERROR: Missing required images";
            if ($numAmbient === 0) {
                $script[] = "# No ambient images provided";
            }
            if ($numFlash === 0) {
                $script[] = "# No flash images provided";
            }
            $script[] = "";
            $script[] = "# Skipping processing - flambient requires both ambient and flash exposures";
            return implode("\n", $script);
        }

        // Step 1: Merge ambient images using Lighten composite
        $script[] = "# Step 1: Merge ambient exposures";
        $script[] = $this->buildMergeCommand($ambientFiles, 'lighten');
        $script[] = "-write mpr:ambient_merge";
        $script[] = "+delete";
        $script[] = "";

        // Step 2: Merge flash images using Lighten composite
        $script[] = "# Step 2: Merge flash exposures";
        $script[] = $this->buildMergeCommand($flashFiles, 'lighten');
        $script[] = "-write mpr:flash_merge";
        $script[] = "+delete";
        $script[] = "";

        // Step 3: Create luminosity mask from ambient
        $script[] = "# Step 3: Create luminosity mask";
        $script[] = "mpr:ambient_merge";
        $script[] = "-channel B";
        $script[] = "-level {$levelLow},{$levelHigh},{$gamma}";
        $script[] = "+channel";
        $script[] = "-write mpr:ambient_mask_alpha";
        $script[] = "+delete";
        $script[] = "";

        // Step 4: Apply mask to flash
        $script[] = "# Step 4: Apply mask to flash";
        $script[] = "mpr:flash_merge mpr:ambient_mask_alpha";
        $script[] = "-compose CopyOpacity -composite";
        $script[] = "-write mpr:flash_mask";
        $script[] = "+delete";
        $script[] = "";

        // Step 5: Luminize blend
        $script[] = "# Step 5: Luminize blend";
        $script[] = "mpr:flash_merge mpr:ambient_merge";
        $script[] = "-compose Luminize -composite";
        $script[] = "-write mpr:luminize_flambient";
        $script[] = "+delete";
        $script[] = "";

        // Step 6: Over composite
        $script[] = "# Step 6: Over composite";
        $script[] = "mpr:luminize_flambient mpr:flash_mask";
        $script[] = "-compose Over -composite";
        $script[] = "-write mpr:ungraded_flambient";
        $script[] = "+delete";
        $script[] = "";

        // Step 7: Final colorize and output
        $script[] = "# Step 7: Final colorize and output";
        $script[] = "mpr:ungraded_flambient mpr:flash_merge";
        $script[] = "-compose Colorize -composite";
        $script[] = "-write \"{$outputPath}\"";
        $script[] = "";
        $script[] = "# Output: {$outputPath}";

        return implode("\n", $script);
    }

    /**
     * Build merge command for composite operations.
     *
     * @param array $files Array of file paths
     * @param string $composeMode Composite mode (lighten, darken, etc.)
     * @return string ImageMagick command fragment
     */
    private function buildMergeCommand(array $files, string $composeMode): string
    {
        $parts = [];

        foreach ($files as $index => $filepath) {
            $quotedPath = "\"{$filepath}\"";

            if ($index === 0) {
                $parts[] = $quotedPath;
            } else {
                $parts[] = "{$quotedPath} -compose {$composeMode} -composite";
            }
        }

        return implode(' ', $parts);
    }
}
