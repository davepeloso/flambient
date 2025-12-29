# `generateScripts` is a public method in the `ImageMagickService` class that generates ImageMagick script files (`.mgk`) for processing ambient and flash image groups.

It orchestrates the creation of per-group ImageMagick scripts and a master shell script to execute them, enabling batch post-processing of photo sets using ImageMagick's advanced compositing features.

---

### Definition
```php
20:55:/Users/davepeloso/Projects/flambient/app/Services/Flambient/ImageMagickService.php
    /**
     * Generate .mgk scripts for all groups.
     */
    public function generateScripts(
        array $groups,
        string $imageDirectory,
        string $scriptsDirectory,
        string $flambientDirectory
    ): array {
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
```

- **Params**:
  - `array $groups`: Associative array where each key is a group number and value contains `'ambient'` and `'flash'` image file arrays.
  - `string $imageDirectory`: Path to directory containing input images.
  - `string $scriptsDirectory`: Output path for generated `.mgk` scripts.
  - `string $flambientDirectory`: Output path for final processed images.
- **Side effects**:
  - Creates `.mgk` script files in `$scriptsDirectory`.
  - Generates a master executable shell script `run_all_scripts.sh`.
  - Writes intermediate and final output images to disk during later execution.
- **Returns**:
  - `array`: List of file paths to the generated `.mgk` script files.

The method ensures required directories exist, iterates over each group to generate individual ImageMagick scripts via `generateGroupScript`, and finally creates a master shell script with `generateMasterScript` if any scripts were produced.

---

### Example Usages

The `generateScripts` method is used primarily in the `FlambientProcessCommand` console command and tested extensively in unit tests.

**Real-world usage in CLI command**:
```php
367:372:/Users/davepeloso/Projects/flambient/app/Console/Commands/FlambientProcessCommand.php
$scripts = $imageMagickService->generateScripts(
    groups: $analyzeResult->data['groups'],
    imageDirectory: $config->imageDirectory,
    scriptsDirectory: "{$config->outputDirectory}/scripts",
    flambientDirectory: "{$config->outputDirectory}/flambient"
);
```

This call generates all necessary ImageMagick scripts after image analysis, preparing for batch processing. The resulting scripts are later executed via `executeAllScripts`.

In testing, it's used to verify correct script generation:
```php
52:52:/Users/davepeloso/Projects/flambient/tests/Unit/ImageMagickServiceTest.php
$result = $this->service->generateScripts($groups, $config);
```

Overall, `generateScripts` is a core part of the Flambient workflow, called once per processing job after grouping images but before execution. It is used in both automated testing and production CLI commands, making it a central component in the image processing pipeline.

---

### Notes

- Despite returning only an array of script paths, `generateScripts` has significant side effects: it writes multiple files to disk, including both `.mgk` scripts and a master `run_all_scripts.sh`.
- The method silently skips groups with missing ambient/flash data but logs warnings inside the generated scripts.
- It relies on internal helper methods like `generateGroupScript` and `generateMasterScript` to build complex ImageMagick and shell commands, abstracting away low-level command construction.

---

### See Also

- `generateGroupScript`: Private method called by `generateScripts` to create individual `.mgk` files per group; defines the ImageMagick operations for blending ambient and flash images.
- `generateMasterScript`: Builds the `run_all_scripts.sh` shell script that sequentially executes all generated `.mgk` files with error handling.
- `executeAllScripts`: Uses the output of `generateScripts` to run all generated scripts via the master shell script.
- `ImageMagickService`: Parent class encapsulating all ImageMagick-related logic, including script generation and execution.