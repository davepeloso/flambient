<?php

namespace App\Services\Stacking;

use App\Contracts\StackingStrategy;
use App\Models\Image;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TimeBasedStackingStrategy implements StackingStrategy
{
    private int $maxSecondsBetweenShots;

    public function __construct(int $maxSecondsBetweenShots = 5)
    {
        $this->maxSecondsBetweenShots = $maxSecondsBetweenShots;
    }

    public function getName(): string
    {
        return 'time_based';
    }

    public function getDescription(): string
    {
        return 'Groups images based on how close they were taken in time';
    }

    public function createStacks(Collection $images): Collection
    {
        $stacks = collect();
        $currentStack = collect();
        $lastTimestamp = null;

        foreach ($images->sortBy('datetime_original') as $image) {
            $currentTimestamp = Carbon::parse($image->datetime_original);

            // Start new stack if time difference is too large
            if ($lastTimestamp && 
                $currentTimestamp->diffInSeconds($lastTimestamp) > $this->maxSecondsBetweenShots) {
                if ($currentStack->isNotEmpty()) {
                    $stacks->push($currentStack);
                    $currentStack = collect();
                }
            }

            $currentStack->push($image);
            $lastTimestamp = $currentTimestamp;
        }

        // Add final stack if not empty
        if ($currentStack->isNotEmpty()) {
            $stacks->push($currentStack);
        }

        return $stacks->filter(fn ($stack) => $this->validateStack($stack));
    }

    public function validateStack(Collection $stackImages): bool
    {
        if ($stackImages->count() < 2) {
            return false;  // Require at least 2 images for time-based stacks
        }

        // Verify images were taken within the time threshold
        $timestamps = $stackImages->map(fn ($img) => Carbon::parse($img->datetime_original))->sort();
        
        $firstTime = $timestamps->first();
        $lastTime = $timestamps->last();
        
        return $lastTime->diffInSeconds($firstTime) <= ($this->maxSecondsBetweenShots * ($stackImages->count() - 1));
    }
}
