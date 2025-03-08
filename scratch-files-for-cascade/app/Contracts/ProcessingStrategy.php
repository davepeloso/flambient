<?php

namespace App\Contracts;

use App\Models\Stack;
use Illuminate\Support\Collection;

/**
 * Interface for implementing new image processing strategies
 * Each strategy represents a different way to process stacks
 * while maintaining strict adherence to delineation field principles
 */
interface ProcessingStrategy
{
    /**
     * Get the unique identifier for this processing strategy
     */
    public function getName(): string;

    /**
     * Get the description of what this strategy does
     */
    public function getDescription(): string;

    /**
     * Process a stack of images
     * Must preserve the relationship between delineation and non-delineation images
     */
    public function processStack(Stack $stack): string;

    /**
     * Get the ImageMagick script template for this strategy
     */
    public function getScriptTemplate(): string;

    /**
     * Validate that a stack can be processed by this strategy
     * Ensures the stack follows delineation field principles
     */
    public function validateStack(Stack $stack): bool;

    /**
     * Get the required parameters for this processing strategy
     */
    public function getRequiredParameters(): array;
}
