<?php

namespace App\Contracts;

use Illuminate\Support\Collection;
use App\Models\Image;

/**
 * Base interface for implementing image grouping strategies
 * Each strategy can implement its own logic for creating stacks
 */
interface StackingStrategy
{
    /**
     * Get the unique identifier for this stacking strategy
     */
    public function getName(): string;

    /**
     * Get a description of how this strategy groups images
     */
    public function getDescription(): string;

    /**
     * Create stacks based on this strategy's grouping logic
     */
    public function createStacks(Collection $images): Collection;

    /**
     * Validate that a stack meets this strategy's requirements
     * Each strategy defines its own validation rules
     */
    public function validateStack(Collection $stackImages): bool;
}
