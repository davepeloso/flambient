<?php

namespace App\Services\Delineation;

use App\Contracts\DelineationStrategy;
use App\Models\Image;
use Illuminate\Support\Collection;

class FlashDelineationStrategy implements DelineationStrategy
{
    private const DELINEATION_VALUE = 'Off, Did not fire';

    public function getFieldName(): string
    {
        return 'flash';
    }

    public function getDelineationValue(): string
    {
        return self::DELINEATION_VALUE;
    }

    public function hasDelineationValue(Image $image): bool
    {
        return $image->flash === self::DELINEATION_VALUE;
    }

    public function createStacks(Collection $images): Collection
    {
        $stacks = collect();
        $currentStack = collect();
        $lastDelineationIndex = -1;

        foreach ($images->sortBy('sequence_column') as $index => $image) {
            $hasDelineationValue = $this->hasDelineationValue($image);
            
            // New stack on new delineation value
            if ($hasDelineationValue && $lastDelineationIndex !== -1) {
                $stacks->push($currentStack);
                $currentStack = collect();
            }
            
            $currentStack->push($image);
            if ($hasDelineationValue) {
                $lastDelineationIndex = $index;
            }
        }

        // Add final stack if not empty
        if ($currentStack->isNotEmpty()) {
            $stacks->push($currentStack);
        }

        return $stacks->filter(fn ($stack) => $this->validateStack($stack));
    }

    public function validateStack(Collection $stackImages): bool
    {
        if ($stackImages->isEmpty()) {
            return false;
        }

        // First image must have delineation value
        $firstImage = $stackImages->first();
        if (!$this->hasDelineationValue($firstImage)) {
            return false;
        }

        // Verify sequence is preserved
        $previousSequence = -1;
        foreach ($stackImages as $image) {
            if ($image->sequence_column <= $previousSequence) {
                return false;
            }
            $previousSequence = $image->sequence_column;
        }

        return true;
    }
}
