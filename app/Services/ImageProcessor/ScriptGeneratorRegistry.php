<?php

namespace App\Services\ImageProcessor;

use App\Services\ImageProcessor\Contracts\ScriptGeneratorInterface;
use Illuminate\Support\Collection;

/**
 * Registry for managing available script generators.
 *
 * Provides discovery and access to all registered image processing techniques.
 */
class ScriptGeneratorRegistry
{
    /** @var Collection<string, ScriptGeneratorInterface> */
    private Collection $generators;

    public function __construct()
    {
        $this->generators = collect();
    }

    /**
     * Register a new generator.
     */
    public function register(ScriptGeneratorInterface $generator): void
    {
        $this->generators->put($generator->getKey(), $generator);
    }

    /**
     * Get a generator by its key.
     */
    public function get(string $key): ?ScriptGeneratorInterface
    {
        return $this->generators->get($key);
    }

    /**
     * Get all registered generators.
     *
     * @return Collection<string, ScriptGeneratorInterface>
     */
    public function all(): Collection
    {
        return $this->generators;
    }

    /**
     * Get generator choices for CLI selection prompts.
     * Returns array of [key => name] pairs.
     *
     * @return array<string, string>
     */
    public function choices(): array
    {
        return $this->generators
            ->mapWithKeys(fn($g) => [$g->getKey() => $g->getName()])
            ->toArray();
    }

    /**
     * Check if a generator exists by key.
     */
    public function has(string $key): bool
    {
        return $this->generators->has($key);
    }

    /**
     * Get the count of registered generators.
     */
    public function count(): int
    {
        return $this->generators->count();
    }
}
