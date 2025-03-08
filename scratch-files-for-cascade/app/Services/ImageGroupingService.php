<?php

namespace App\Services;

use App\Contracts\StackingStrategy;
use App\Models\Image;
use App\Services\Stacking\FlashAmbientStackingStrategy;
use App\Services\Stacking\TimeBasedStackingStrategy;
use Illuminate\Support\Collection;
use RuntimeException;

class ImageGroupingService
{
    /**
     * @var array<string, StackingStrategy>
     */
    private array $strategies = [];

    /**
     * Register built-in stacking strategies
     */
    public function __construct()
    {
        // Register default strategies
        $this->registerStrategy(new FlashAmbientStackingStrategy());
        $this->registerStrategy(new TimeBasedStackingStrategy());
    }

    /**
     * Register a new stacking strategy
     */
    public function registerStrategy(StackingStrategy $strategy): void
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * Get all registered stacking strategies
     *
     * @return array<string, StackingStrategy>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Create stacks using the specified strategy
     */
    public function createStacks(string $strategyName, Collection $images): Collection
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new RuntimeException("No strategy registered with name: {$strategyName}");
        }

        return $this->strategies[$strategyName]->createStacks($images);
    }

    /**
     * Get description of a stacking strategy
     */
    public function getStrategyDescription(string $strategyName): string
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new RuntimeException("No strategy registered with name: {$strategyName}");
        }

        return $this->strategies[$strategyName]->getDescription();
    }
}
