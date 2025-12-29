# `ImageMagickService` is a service class that generates and executes ImageMagick scripts for photo blending.

It automates the creation of `.mgk` script files to composite ambient and flash exposures in real estate photography, enabling batch post-processing via ImageMagick’s advanced compositing engine.

---

### Definition

```php
8:245:/Users/davepeloso/Projects/flambient/app/Services/Flambient/ImageMagickService.php
class ImageMagickService
{
    public function __construct(
        private readonly string $binary = 'magick',
        private readonly string $levelLow = '40%',
        private readonly string $levelHigh = '140%',
        private readonly string $gamma = '1.0',
        private readonly string $outputPrefix = 'flambient',
        private readonly bool $enableDarkenExport = true,
        private readonly string $darkenSuffix = '_tmp',
    ) {}

    /**
     * Generate .mgk scripts for all groups.
     */
    public function generateScripts(
        array $groups,
        string $imageDirectory,
        string $scriptsDirectory,
        string $flambientDirectory
    ): array {
        // Ensure output directories exist
        File::ensureDirectoryExists($scriptsDirectory);
        File::ensureDirectoryExists($flambientDirectory);

        $generatedScripts = [];

        foreach ($groups as $groupNumber => $group) {
            $scriptPath = $this->generateGroupScript(
                $groupNumber,
                $group['ambient'] ?? [],
                $group['flash'] ?? [],
                $imageDirectory,
                $scriptsDirectory,
                $flambientDirectory
            );

            if ($scriptPath) {
                $generatedScripts[] = $scriptPath;
            }
        }

        // Generate master run_all_scripts.sh
        if (!empty($generatedScripts)) {
            $this->generateMasterScript($scriptsDirectory, $generatedScripts);
        }

        return $generatedScripts;
    }

    /**
     * Execute all scripts in a directory.
     */
    public function executeAllScripts(string $scriptsDirectory): array
    {
        $masterScript = "{$scriptsDirectory}/run_all_scripts.sh";

        if (!file_exists($masterScript)) {
            return [
                'success' => false,
                'error' => 'Master script not found',
                'results' => [],
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
}
```

- **Purpose**: Generates ImageMagick script files (`.mgk`) and shell scripts to automate image blending workflows.
- **Params**:
  - `$groups`: Associative array of image groups, each with `ambient` and `flash` file lists.
  - `$imageDirectory`: Path where input images are stored.
  - `$scriptsDirectory`: Output directory for generated `.mgk` scripts.
  - `$flambientDirectory`: Output directory for final blended images.
- **Side effects**:
  - Creates `.mgk` script files per image group.
  - Generates a master `run_all_scripts.sh` shell script with error handling.
  - Executes system commands via `Process`.
- **Returns**:
  - `generateScripts()`: Array of paths to generated `.mgk` files.
  - `executeAllScripts()`: Array with `success`, `output`, `error`, and `exit_code`.

---

### Example Usages

The `ImageMagickService` is used in the `FlambientProcessCommand` console command to process real estate photo sets by blending ambient and flash exposures using ImageMagick's compositing features.

```php
354:358:/Users/davepeloso/Projects/flambient/app/Console/Commands/FlambientProcessCommand.php
$imageMagickService = new ImageMagickService(
    levelLow: $config->levelLow,
    levelHigh: $config->levelHigh,
    gamma: $config->gamma,
    outputPrefix: $config->outputPrefix,
);
```

After instantiation, it generates scripts based on grouped images:

```php
367:375:/Users/davepeloso/Projects/flambient/app/Console/Commands/FlambientProcessCommand.php
$scripts = $imageMagickService->generateScripts(
    groups: $analyzeResult->data['groups'],
    imageDirectory: $config->imageDirectory,
    scriptsDirectory: "{$config->outputDirectory}/scripts",
    flambientDirectory: "{$config->outputDirectory}/flambient"
);
```

Then executes them:

```php
380:382:/Users/davepeloso/Projects/flambient/app/Console/Commands/FlambientProcessCommand.php
$processResult = $imageMagickService->executeAllScripts(
    scriptsDirectory: "{$config->outputDirectory}/scripts"
);
```

This pattern is also tested in unit tests to verify correct script generation and execution behavior.

Overall, `ImageMagickService` is a core component in the **local image processing pipeline**, primarily called by:
- `FlambientProcessCommand`: Main CLI entry point.
- `ImageMagickServiceTest`: Unit tests validating script logic.

It is not used in cloud-only workflows and only runs when local blending is enabled.

---

### Notes

- The service writes intermediate **memory program registers (mpr:)** in ImageMagick scripts to pass image data between operations without disk I/O.
- It implements a **five-step blending algorithm**:
  1. Merge ambient images using `lighten` composite.
  2. Merge flash images (optionally export darkened version).
  3. Use ambient brightness as an alpha mask for flash.
  4. Apply `Luminize` blend to transfer ambient tone.
  5. Use `Colorize` to apply final color from flash.
- The `enableDarkenExport` option saves a darkened composite of flash images—useful for debugging or alternative blending strategies—even though it's not part of the final output.

---

### See Also

- `generateGroupScript`: Private method that constructs individual `.mgk` scripts per group; defines the core ImageMagick command sequence.
- `generateMasterScript`: Builds the `run_all_scripts.sh` shell script that orchestrates execution with progress reporting and error handling.
- `executeAllScripts`: Public method that runs the master script with a 30-minute timeout, used after `generateScripts`.
- `config/flambient.php`: Configuration file defining default values like `output_prefix` and `level_low`, which can be overridden via environment variables.
- `ExifService`: Precedes this service in the workflow; classifies images into ambient/flash before grouping.