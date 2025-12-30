# Refactoring Plan: From "Flambient" to Modular Image Processor

## Executive Summary

Your current architecture is tightly coupled to the "Flambient" technique. This plan transforms it into a **plugin-based script generator** where Flambient becomes one of many available processing scripts.

---

## Current State Analysis

### What You Have

```
app/
├── Console/Commands/
│   └── FlambientProcessCommand.php      ← Tightly coupled to flambient
├── Services/
│   └── Flambient/                        ← Namespace implies single purpose
│       └── ImageMagickService.php        ← Hardcoded flambient blending logic
config/
└── flambient.php                         ← All config under "flambient"
storage/
└── flambient/                            ← Output locked to flambient
```

### The Problems

1. **Naming**: Everything says "Flambient" when the *engine* should be generic
2. **Hardcoded Logic**: `ImageMagickService.generateGroupScript()` contains the 5-step flambient algorithm directly
3. **No Extension Point**: Adding a new script type requires modifying core classes
4. **Configuration Coupling**: `config/flambient.php` mixes engine settings with technique-specific settings

---

## Target Architecture

```
app/
├── Console/Commands/
│   └── ProcessCommand.php                    ← Generic processor command
├── Services/
│   └── ImageProcessor/                       ← Renamed, generic namespace
│       ├── ImageMagickService.php            ← Core engine (executes scripts)
│       ├── ScriptGeneratorInterface.php      ← Contract for all generators
│       ├── ScriptGeneratorRegistry.php       ← Discovers & manages generators
│       └── Generators/                       ← Plugin directory
│           ├── FlambientGenerator.php        ← Your existing flambient logic
│           ├── HDRMergeGenerator.php         ← Example: HDR from brackets
│           ├── FocusStackGenerator.php       ← Example: Focus stacking
│           └── DMECGenerator.php             ← Your D-MEC technique!
config/
├── image-processor.php                       ← Generic engine config
└── generators/                               ← Per-generator configs
    ├── flambient.php
    ├── hdr-merge.php
    └── dmec.php
storage/
└── image-processor/
    └── {project-name}/
        ├── scripts/
        └── output/
```

---

## Phase 1: Create the Plugin Architecture (Non-Breaking)

### Step 1.1: Define the Script Generator Interface

```php
// app/Services/ImageProcessor/Contracts/ScriptGeneratorInterface.php

namespace App\Services\ImageProcessor\Contracts;

interface ScriptGeneratorInterface
{
    /**
     * Unique identifier for this generator (e.g., 'flambient', 'hdr-merge')
     */
    public function getKey(): string;

    /**
     * Human-readable name for CLI selection
     */
    public function getName(): string;

    /**
     * Description shown in help text
     */
    public function getDescription(): string;

    /**
     * Configuration schema (for interactive prompts)
     * Returns array of parameter definitions
     */
    public function getConfigurationSchema(): array;

    /**
     * Generate .mgk script content for a single group
     *
     * @param array $images         Grouped images with classifications
     * @param array $config         User-provided configuration
     * @param string $outputPath    Where final image should be written
     * @return string               The .mgk script content
     */
    public function generateScript(
        array $images,
        array $config,
        string $outputPath
    ): string;

    /**
     * How should images be grouped/classified for this technique?
     * Returns classification strategy or null if no grouping needed
     */
    public function getClassificationStrategy(): ?string;
}
```

### Step 1.2: Create the Registry

```php
// app/Services/ImageProcessor/ScriptGeneratorRegistry.php

namespace App\Services\ImageProcessor;

use App\Services\ImageProcessor\Contracts\ScriptGeneratorInterface;
use Illuminate\Support\Collection;

class ScriptGeneratorRegistry
{
    /** @var Collection<string, ScriptGeneratorInterface> */
    private Collection $generators;

    public function __construct()
    {
        $this->generators = collect();
    }

    public function register(ScriptGeneratorInterface $generator): void
    {
        $this->generators->put($generator->getKey(), $generator);
    }

    public function get(string $key): ?ScriptGeneratorInterface
    {
        return $this->generators->get($key);
    }

    public function all(): Collection
    {
        return $this->generators;
    }

    public function choices(): array
    {
        return $this->generators
            ->mapWithKeys(fn($g) => [$g->getKey() => $g->getName()])
            ->toArray();
    }
}
```

### Step 1.3: Create a Service Provider

```php
// app/Providers/ImageProcessorServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ImageProcessor\ScriptGeneratorRegistry;
use App\Services\ImageProcessor\Generators\FlambientGenerator;
// Future generators imported here

class ImageProcessorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ScriptGeneratorRegistry::class, function () {
            $registry = new ScriptGeneratorRegistry();
            
            // Register all available generators
            $registry->register(new FlambientGenerator());
            // $registry->register(new HDRMergeGenerator());
            // $registry->register(new DMECGenerator());
            
            return $registry;
        });
    }
}
```

---

## Phase 2: Extract Flambient Logic into a Generator

### Step 2.1: Create FlambientGenerator

```php
// app/Services/ImageProcessor/Generators/FlambientGenerator.php

namespace App\Services\ImageProcessor\Generators;

use App\Services\ImageProcessor\Contracts\ScriptGeneratorInterface;

class FlambientGenerator implements ScriptGeneratorInterface
{
    public function getKey(): string
    {
        return 'flambient';
    }

    public function getName(): string
    {
        return 'Flambient (Ambient + Flash Blend)';
    }

    public function getDescription(): string
    {
        return 'Combines ambient and flash exposures using luminosity masking for natural-looking real estate photos.';
    }

    public function getClassificationStrategy(): ?string
    {
        return 'flash'; // Uses Flash EXIF field to classify
    }

    public function getConfigurationSchema(): array
    {
        return [
            'level_low' => [
                'type' => 'text',
                'label' => 'Level Low',
                'default' => '40%',
                'description' => 'Black point adjustment for mask generation',
            ],
            'level_high' => [
                'type' => 'text',
                'label' => 'Level High',
                'default' => '140%',
                'description' => 'White point adjustment for mask generation',
            ],
            'gamma' => [
                'type' => 'text',
                'label' => 'Gamma',
                'default' => '1.0',
                'description' => 'Gamma correction for midtones',
            ],
            'enable_darken_export' => [
                'type' => 'confirm',
                'label' => 'Export darkened flash composite?',
                'default' => true,
            ],
        ];
    }

    public function generateScript(
        array $images,
        array $config,
        string $outputPath
    ): string {
        $ambientFiles = $images['ambient'] ?? [];
        $flashFiles = $images['flash'] ?? [];
        
        $levelLow = $config['level_low'] ?? '40%';
        $levelHigh = $config['level_high'] ?? '140%';
        $gamma = $config['gamma'] ?? '1.0';
        
        // This is your existing 5-step algorithm, extracted
        $script = $this->buildFlambientScript(
            $ambientFiles,
            $flashFiles,
            $levelLow,
            $levelHigh,
            $gamma,
            $outputPath
        );

        return $script;
    }

    private function buildFlambientScript(
        array $ambientFiles,
        array $flashFiles,
        string $levelLow,
        string $levelHigh,
        string $gamma,
        string $outputPath
    ): string {
        $script = [];
        
        // Step 1: Merge ambient images using lighten composite
        $script[] = "# Step 1: Merge ambient exposures";
        $script[] = "( " . implode(" ", $ambientFiles) . " -compose Lighten -flatten )";
        $script[] = "-write mpr:ambient";
        $script[] = "+delete";
        
        // Step 2: Merge flash images
        $script[] = "";
        $script[] = "# Step 2: Merge flash exposures";
        $script[] = "( " . implode(" ", $flashFiles) . " -compose Lighten -flatten )";
        $script[] = "-write mpr:flash";
        $script[] = "+delete";
        
        // Step 3: Create luminosity mask from ambient
        $script[] = "";
        $script[] = "# Step 3: Create luminosity mask";
        $script[] = "mpr:ambient";
        $script[] = "-colorspace Gray";
        $script[] = "-level {$levelLow},{$levelHigh},{$gamma}";
        $script[] = "-write mpr:mask";
        $script[] = "+delete";
        
        // Step 4: Apply Luminize blend
        $script[] = "";
        $script[] = "# Step 4: Luminize blend";
        $script[] = "mpr:flash mpr:ambient";
        $script[] = "-compose Luminize -composite";
        $script[] = "-write mpr:luminized";
        $script[] = "+delete";
        
        // Step 5: Final colorize composite
        $script[] = "";
        $script[] = "# Step 5: Final Colorize composite";
        $script[] = "mpr:luminized mpr:flash mpr:mask";
        $script[] = "-compose Over -composite";
        $script[] = "-write \"{$outputPath}\"";
        
        return implode("\n", $script);
    }
}
```

---

## Phase 3: Refactor the Core Service

### Step 3.1: Simplified ImageMagickService

```php
// app/Services/ImageProcessor/ImageMagickService.php

namespace App\Services\ImageProcessor;

use App\Services\ImageProcessor\Contracts\ScriptGeneratorInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class ImageMagickService
{
    public function __construct(
        private readonly string $binary = 'magick',
    ) {}

    /**
     * Generate scripts using the specified generator
     */
    public function generateScripts(
        ScriptGeneratorInterface $generator,
        array $groups,
        array $config,
        string $imageDirectory,
        string $scriptsDirectory,
        string $outputDirectory
    ): array {
        File::ensureDirectoryExists($scriptsDirectory);
        File::ensureDirectoryExists($outputDirectory);

        $generatedScripts = [];
        $prefix = $generator->getKey();

        foreach ($groups as $groupNumber => $group) {
            $outputPath = "{$outputDirectory}/{$prefix}_{$groupNumber}.jpg";
            
            $scriptContent = $generator->generateScript(
                $group,
                $config,
                $outputPath
            );

            $scriptPath = "{$scriptsDirectory}/{$prefix}_{$groupNumber}.mgk";
            File::put($scriptPath, $scriptContent);
            
            $generatedScripts[] = $scriptPath;
        }

        if (!empty($generatedScripts)) {
            $this->generateMasterScript($scriptsDirectory, $generatedScripts);
        }

        return $generatedScripts;
    }

    /**
     * Execute all scripts via master script
     */
    public function executeAllScripts(string $scriptsDirectory): array
    {
        $masterScript = "{$scriptsDirectory}/run_all_scripts.sh";

        if (!file_exists($masterScript)) {
            return [
                'success' => false,
                'error' => 'Master script not found',
            ];
        }

        $result = Process::timeout(1800)->run("bash \"{$masterScript}\"");

        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'error' => $result->errorOutput(),
            'exit_code' => $result->exitCode(),
        ];
    }

    private function generateMasterScript(string $directory, array $scripts): void
    {
        $lines = ['#!/bin/bash', 'set -e', ''];
        
        foreach ($scripts as $script) {
            $lines[] = "echo \"Processing: {$script}\"";
            $lines[] = "{$this->binary} -script \"{$script}\"";
        }
        
        $lines[] = '';
        $lines[] = 'echo "All scripts completed successfully"';
        
        $masterPath = "{$directory}/run_all_scripts.sh";
        File::put($masterPath, implode("\n", $lines));
        chmod($masterPath, 0755);
    }
}
```

---

## Phase 4: Rename the Command

### Step 4.1: Create New Generic Command

```php
// app/Console/Commands/ProcessCommand.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImageProcessor\ScriptGeneratorRegistry;
use App\Services\ImageProcessor\ImageMagickService;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

class ProcessCommand extends Command
{
    protected $signature = 'process:images
                            {--technique= : Processing technique to use}
                            {--project= : Project name}
                            {--dir= : Image directory}';

    protected $description = 'Process images using various ImageMagick techniques';

    public function handle(
        ScriptGeneratorRegistry $registry,
        ImageMagickService $imageMagick
    ): int {
        // 1. Select technique
        $techniqueKey = $this->option('technique') ?: select(
            label: 'Select processing technique',
            options: $registry->choices(),
            hint: 'Choose how images should be processed'
        );

        $generator = $registry->get($techniqueKey);
        
        if (!$generator) {
            $this->error("Unknown technique: {$techniqueKey}");
            return 1;
        }

        $this->info("Using: {$generator->getName()}");
        $this->line($generator->getDescription());

        // 2. Get project details
        $projectName = $this->option('project') ?: text(
            label: 'Project name',
            required: true
        );

        $imageDirectory = $this->option('dir') ?: text(
            label: 'Image directory',
            required: true
        );

        // 3. Collect technique-specific configuration
        $config = $this->collectConfiguration($generator);

        // 4. Classify and group images
        $groups = $this->classifyImages($imageDirectory, $generator);

        // 5. Generate scripts
        $outputBase = storage_path("image-processor/{$projectName}");
        
        $scripts = $imageMagick->generateScripts(
            generator: $generator,
            groups: $groups,
            config: $config,
            imageDirectory: $imageDirectory,
            scriptsDirectory: "{$outputBase}/scripts",
            outputDirectory: "{$outputBase}/output"
        );

        $this->info("Generated " . count($scripts) . " scripts");

        // 6. Execute
        if (confirm('Execute scripts now?', true)) {
            $result = $imageMagick->executeAllScripts("{$outputBase}/scripts");
            
            if ($result['success']) {
                $this->info("✓ Processing complete!");
            } else {
                $this->error("Processing failed: " . $result['error']);
                return 1;
            }
        }

        return 0;
    }

    private function collectConfiguration($generator): array
    {
        $config = [];
        $schema = $generator->getConfigurationSchema();

        foreach ($schema as $key => $field) {
            $config[$key] = match ($field['type']) {
                'text' => text(
                    label: $field['label'],
                    default: $field['default'] ?? '',
                    hint: $field['description'] ?? ''
                ),
                'confirm' => confirm(
                    label: $field['label'],
                    default: $field['default'] ?? false
                ),
                default => $field['default'] ?? null,
            };
        }

        return $config;
    }

    private function classifyImages(string $directory, $generator): array
    {
        // Use ExifService + generator's classification strategy
        // Returns grouped images
        // ... implementation details ...
        return [];
    }
}
```

---

## Phase 5: Add Your D-MEC Technique!

Here's a skeleton for your Dissimilar Multiple Exposure Composition technique:

```php
// app/Services/ImageProcessor/Generators/DMECGenerator.php

namespace App\Services\ImageProcessor\Generators;

use App\Services\ImageProcessor\Contracts\ScriptGeneratorInterface;

class DMECGenerator implements ScriptGeneratorInterface
{
    public function getKey(): string
    {
        return 'dmec';
    }

    public function getName(): string
    {
        return 'D-MEC (Dissimilar Multiple Exposure Composition)';
    }

    public function getDescription(): string
    {
        return 'Cherry-pick advantageous attributes from multiple dissimilar exposures (flash, ambient, HDR, etc.) without moving the camera.';
    }

    public function getClassificationStrategy(): ?string
    {
        return 'custom'; // User defines classification during workflow
    }

    public function getConfigurationSchema(): array
    {
        return [
            'base_exposure' => [
                'type' => 'select',
                'label' => 'Base exposure type',
                'options' => ['ambient', 'flash', 'bracket_0'],
                'default' => 'ambient',
                'description' => 'Which exposure provides the foundation?',
            ],
            'blend_mode' => [
                'type' => 'select',
                'label' => 'Primary blend mode',
                'options' => ['luminosity', 'color', 'overlay', 'soft-light'],
                'default' => 'luminosity',
            ],
            'mask_source' => [
                'type' => 'select',
                'label' => 'Mask generation source',
                'options' => ['luminosity', 'saturation', 'manual'],
                'default' => 'luminosity',
            ],
            'layer_count' => [
                'type' => 'text',
                'label' => 'Number of exposure layers',
                'default' => '2',
            ],
        ];
    }

    public function generateScript(
        array $images,
        array $config,
        string $outputPath
    ): string {
        // Your D-MEC algorithm here
        // The beauty is you can define ANY ImageMagick workflow
        
        $script = [];
        $script[] = "# D-MEC Composite";
        $script[] = "# Cherry-picking the best from each exposure";
        
        // Example: Load base, blend others selectively
        $baseExposure = $images[$config['base_exposure']][0] ?? $images[array_key_first($images)][0];
        
        $script[] = "( \"{$baseExposure}\" )";
        $script[] = "-write mpr:base";
        
        // Add layers based on config...
        
        $script[] = "-write \"{$outputPath}\"";
        
        return implode("\n", $script);
    }
}
```

---

## Phase 6: Configuration Restructure

### Step 6.1: New Generic Config

```php
// config/image-processor.php

return [
    'binary' => env('IMAGEMAGICK_BINARY', 'magick'),
    
    'storage_path' => storage_path('image-processor'),
    
    'defaults' => [
        'timeout' => 1800,  // 30 minutes
        'memory_limit' => '2GB',
    ],
    
    // Generator-specific configs are auto-loaded from config/generators/
];
```

### Step 6.2: Per-Generator Configs

```php
// config/generators/flambient.php

return [
    'level_low' => env('FLAMBIENT_LEVEL_LOW', '40%'),
    'level_high' => env('FLAMBIENT_LEVEL_HIGH', '140%'),
    'gamma' => env('FLAMBIENT_GAMMA', '1.0'),
    'enable_darken_export' => env('FLAMBIENT_DARKEN_EXPORT', true),
];
```

```php
// config/generators/dmec.php

return [
    'default_blend_mode' => env('DMEC_BLEND_MODE', 'luminosity'),
    'max_layers' => env('DMEC_MAX_LAYERS', 5),
];
```

---

## Migration Checklist

### Files to Rename/Move

| Current | Target |
|---------|--------|
| `app/Services/Flambient/` | `app/Services/ImageProcessor/` |
| `app/Console/Commands/FlambientProcessCommand.php` | `app/Console/Commands/ProcessCommand.php` |
| `config/flambient.php` | `config/image-processor.php` + `config/generators/flambient.php` |
| `storage/flambient/` | `storage/image-processor/` |

### Artisan Signature Changes

| Current | Target |
|---------|--------|
| `php artisan flambient:process` | `php artisan process:images` |
| `php artisan flambient:status` | `php artisan process:status` |

### Backward Compatibility

If you need to maintain the old command temporarily:

```php
// app/Console/Commands/FlambientProcessCommand.php (deprecated wrapper)

class FlambientProcessCommand extends Command
{
    protected $signature = 'flambient:process {--dir=} {--local}';
    
    public function handle(): int
    {
        $this->warn('⚠️  flambient:process is deprecated. Use process:images --technique=flambient');
        
        return $this->call('process:images', [
            '--technique' => 'flambient',
            '--dir' => $this->option('dir'),
        ]);
    }
}
```

---

## Summary: The Plugin Pattern

```
┌─────────────────────────────────────────────────────────────────┐
│                      ProcessCommand (CLI)                        │
│  "What technique?" → Registry → Generator → ImageMagickService  │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                   ScriptGeneratorRegistry                        │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌───────────┐  │
│  │  Flambient  │ │  HDR Merge  │ │ Focus Stack │ │   D-MEC   │  │
│  │  Generator  │ │  Generator  │ │  Generator  │ │ Generator │  │
│  └─────────────┘ └─────────────┘ └─────────────┘ └───────────┘  │
└─────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│                     ImageMagickService                           │
│        generateScripts() → .mgk files → executeAllScripts()     │
└─────────────────────────────────────────────────────────────────┘
```

**To add a new technique:**
1. Create `app/Services/ImageProcessor/Generators/YourGenerator.php`
2. Implement `ScriptGeneratorInterface`
3. Register in `ImageProcessorServiceProvider`
4. Optionally add `config/generators/your-technique.php`

That's it! No core code changes needed.
