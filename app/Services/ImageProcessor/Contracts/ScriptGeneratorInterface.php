<?php

namespace App\Services\ImageProcessor\Contracts;

/**
 * Interface for ImageMagick script generators.
 *
 * Each generator implements a specific image processing technique
 * (e.g., Flambient, HDR Merge, Focus Stacking, D-MEC).
 */
interface ScriptGeneratorInterface
{
    /**
     * Unique identifier for this generator (e.g., 'flambient', 'hdr-merge', 'dmec')
     */
    public function getKey(): string;

    /**
     * Human-readable name for CLI selection and display
     */
    public function getName(): string;

    /**
     * Description shown in help text and selection prompts
     */
    public function getDescription(): string;

    /**
     * Configuration schema for interactive prompts.
     * Returns array of parameter definitions with type, label, default, etc.
     *
     * @return array<string, array{type: string, label: string, default: mixed, description?: string, options?: array}>
     */
    public function getConfigurationSchema(): array;

    /**
     * Generate ImageMagick script content for a single group of images.
     *
     * @param array $images Array of image classifications/groups (e.g., ['ambient' => [...], 'flash' => [...]])
     * @param array $config User-provided configuration from getConfigurationSchema()
     * @param string $outputPath Full path where the final image should be written
     * @return string The complete .mgk script content
     */
    public function generateScript(
        array $images,
        array $config,
        string $outputPath
    ): string;

    /**
     * How should images be grouped/classified for this technique?
     * Returns classification strategy identifier or null if no grouping needed.
     *
     * Examples: 'flash', 'exposure_program', 'custom', null
     */
    public function getClassificationStrategy(): ?string;
}
