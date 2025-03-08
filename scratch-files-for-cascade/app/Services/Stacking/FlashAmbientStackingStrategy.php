<?php

namespace App\Services\Stacking;

use App\Contracts\StackingStrategy;
use App\Models\Image;
use Illuminate\Support\Collection;

class FlashAmbientStackingStrategy implements StackingStrategy
{
    // Specific flash-ambient delineation values from verified implementation
    private const DELINEATION_VALUES = [
        'flash' => 'Off, Did not fire',
        'exposure_mode' => 'Auto',
        'white_balance' => 'Auto',
        'iso' => 400
    ];

    private string $field;

    public function __construct(string $field = 'flash')
    {
        if (!isset(self::DELINEATION_VALUES[$field])) {
            throw new \InvalidArgumentException("Invalid delineation field: {$field}");
        }
        $this->field = $field;
    }

    public function getName(): string
    {
        return 'flash_ambient';
    }

    public function getDescription(): string
    {
        return 'Groups images based on flash/ambient transitions using verified delineation field values';
    }

    public function createStacks(Collection $images): Collection
    {
        $stacks = collect();
        $currentStack = collect();
        $lastAmbientIndex = -1;

        foreach ($images->sortBy('sequence_column') as $index => $image) {
            $isAmbient = $this->isAmbientShot($image);
            
            // New stack on new ambient shot
            if ($isAmbient && $lastAmbientIndex !== -1) {
                $stacks->push($currentStack);
                $currentStack = collect();
            }
            
            $currentStack->push($image);
            if ($isAmbient) {
                $lastAmbientIndex = $index;
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

        // First image must be ambient according to verified implementation
        $firstImage = $stackImages->first();
        if (!$this->isAmbientShot($firstImage)) {
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

    /**
     * Determine if an image is an ambient shot based on the verified implementation
     */
    private function isAmbientShot(Image $image): bool
    {
        $value = $image->{$this->field};
        
        if ($this->field === 'iso') {
            return (int)$value === self::DELINEATION_VALUES[$this->field];
        }
        
        return $value === self::DELINEATION_VALUES[$this->field];
    }
}
