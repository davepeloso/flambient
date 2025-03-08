<?php

namespace App\Services;

use App\Contracts\ProcessingStrategy;
use App\Models\Stack;
use App\Services\Processing\AlphaProcessingStrategy;
use Illuminate\Support\Collection;
use RuntimeException;

class ImageProcessingService
{
    /**
     * @var array<string, ProcessingStrategy>
     */
    private array $strategies = [];

    private ExifExtractionService $exifService;

    public function __construct(ExifExtractionService $exifService)
    {
        $this->exifService = $exifService;
        $this->registerStrategy(new AlphaProcessingStrategy($exifService));
        // Additional strategies can be registered here
    }

    /**
     * Register a new processing strategy
     */
    public function registerStrategy(ProcessingStrategy $strategy): void
    {
        $this->strategies[$strategy->getName()] = $strategy;
    }

    /**
     * Get all registered processing strategies
     *
     * @return array<string, ProcessingStrategy>
     */
    public function getStrategies(): array
    {
        return $this->strategies;
    }

    /**
     * Process a stack using the specified strategy
     * Ensures strict adherence to delineation field principles
     */
    public function processStack(string $strategyName, Stack $stack, array $parameters = []): string
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new RuntimeException("No strategy registered with name: {$strategyName}");
        }

        $strategy = $this->strategies[$strategyName];
        
        // Validate stack follows delineation principles
        if (!$strategy->validateStack($stack)) {
            throw new RuntimeException("Stack does not meet requirements for strategy: {$strategyName}");
        }

        // Validate and merge parameters
        $params = $this->validateParameters($strategy, $parameters);
        
        return $strategy->processStack($stack, $params);
    }

    /**
     * Get script template for a strategy
     */
    public function getScriptTemplate(string $strategyName): string
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new RuntimeException("No strategy registered with name: {$strategyName}");
        }

        return $this->strategies[$strategyName]->getScriptTemplate();
    }

    /**
     * Get required parameters for a strategy
     */
    public function getRequiredParameters(string $strategyName): array
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new RuntimeException("No strategy registered with name: {$strategyName}");
        }

        return $this->strategies[$strategyName]->getRequiredParameters();
    }

    /**
     * Validate and merge parameters with defaults
     */
    private function validateParameters(ProcessingStrategy $strategy, array $parameters): array
    {
        $required = $strategy->getRequiredParameters();
        $validated = [];

        foreach ($required as $name => $config) {
            if (isset($parameters[$name])) {
                $value = $parameters[$name];
                
                // Type validation
                if ($config['type'] === 'integer' && !is_int($value)) {
                    throw new RuntimeException("Parameter {$name} must be an integer");
                }
                if ($config['type'] === 'float' && !is_float($value)) {
                    throw new RuntimeException("Parameter {$name} must be a float");
                }

                // Range validation
                if (isset($config['min']) && $value < $config['min']) {
                    throw new RuntimeException("Parameter {$name} must be >= {$config['min']}");
                }
                if (isset($config['max']) && $value > $config['max']) {
                    throw new RuntimeException("Parameter {$name} must be <= {$config['max']}");
                }

                $validated[$name] = $value;
            } else {
                // Use default if available
                if (isset($config['default'])) {
                    $validated[$name] = $config['default'];
                } else {
                    throw new RuntimeException("Required parameter missing: {$name}");
                }
            }
        }

        return $validated;
    }
}
