<?php

namespace App\Services\Processing;

use App\Contracts\ProcessingStrategy;
use App\Models\Stack;
use App\Services\ExifExtractionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AlphaProcessingStrategy implements ProcessingStrategy
{
    private ExifExtractionService $exifService;

    public function __construct(ExifExtractionService $exifService)
    {
        $this->exifService = $exifService;
    }

    public function getName(): string
    {
        return 'alpha';
    }

    public function getDescription(): string
    {
        return 'Processes images using delineation field transitions for masking and blending';
    }

    public function processStack(Stack $stack): string
    {
        if (!$this->validateStack($stack)) {
            throw new \RuntimeException('Invalid stack for processing');
        }

        $images = $stack->images()->ordered()->get();
        $delineationImage = $images->first(); // First image must have delineation value
        $nonDelineationImages = $images->slice(1);

        $scriptPath = $this->generateScript($delineationImage, $nonDelineationImages);
        return $this->executeScript($scriptPath);
    }

    public function getScriptTemplate(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env magick-script

# Variables will be replaced:
# {DELINEATION_IMAGE} - Path to image with delineation value
# {NON_DELINEATION_IMAGES} - Space-separated paths to other images
# {OUTPUT_PATH} - Path for processed output
# {MASK_PATH} - Path for temporary mask file

# Create mask from delineation image
{DELINEATION_IMAGE} -channel B -separate -level 80%,100%,5.0 -write mpr:mask +delete

# Process non-delineation images
{NON_DELINEATION_IMAGES} -evaluate-sequence mean -write mpr:blend +delete

# Combine using mask
( mpr:mask ) ( mpr:blend ) -compose over -composite {OUTPUT_PATH}
SCRIPT;
    }

    public function validateStack(Stack $stack): bool
    {
        $images = $stack->images()->ordered()->get();
        
        if ($images->isEmpty()) {
            return false;
        }

        // First image must have delineation value
        $firstImage = $images->first();
        if (!$this->exifService->isDelineationValue($stack->delineation_field, $firstImage->{$stack->delineation_field})) {
            return false;
        }

        // Must have at least one non-delineation image
        if ($images->count() < 2) {
            return false;
        }

        return true;
    }

    public function getRequiredParameters(): array
    {
        return [
            'quality' => [
                'type' => 'integer',
                'default' => 90,
                'min' => 1,
                'max' => 100
            ],
            'mask_threshold' => [
                'type' => 'float',
                'default' => 5.0,
                'min' => 0.1,
                'max' => 10.0
            ]
        ];
    }

    private function generateScript(Image $delineationImage, Collection $nonDelineationImages): string
    {
        $template = $this->getScriptTemplate();
        
        $scriptPath = Storage::path("processing/{$delineationImage->stack_id}.msl");
        $outputPath = Storage::path("output/{$delineationImage->stack_id}.jpg");
        
        $replacements = [
            '{DELINEATION_IMAGE}' => $delineationImage->path,
            '{NON_DELINEATION_IMAGES}' => $nonDelineationImages->pluck('path')->join(' '),
            '{OUTPUT_PATH}' => $outputPath,
            '{MASK_PATH}' => Storage::path("temp/mask_{$delineationImage->stack_id}.mpc")
        ];

        $script = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $template
        );

        file_put_contents($scriptPath, $script);
        return $scriptPath;
    }

    private function executeScript(string $scriptPath): string
    {
        $process = new Process(['magick', '-script', $scriptPath]);
        $process->setTimeout(300); // 5 minutes
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException("Processing failed: {$process->getErrorOutput()}");
        }

        return str_replace('.msl', '.jpg', $scriptPath);
    }
}
