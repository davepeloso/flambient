# `FlambientProcessCommand` is a Laravel console command that orchestrates a photography workflow combining ImageMagick and Imagen AI.

It enables photographers to process "flambient" images—those captured with both ambient and flash lighting—by blending exposures locally and optionally enhancing them via Google's Imagen AI cloud service. The command provides an interactive CLI for configuration, supports multiple processing modes, and integrates with a database to track workflow runs.

---

### Definition

`FlambientProcessCommand` is a class extending Laravel's `Command`, designed to be invoked via Artisan (`php artisan flambient:process`). It guides users through a step-by-step workflow for image processing, collecting inputs interactively using the `Laravel\Prompts` library.

```php
29:1027:/Users/davepeloso/Projects/flambient/app/Console/Commands/FlambientProcessCommand.php
class FlambientProcessCommand extends Command
{
    protected $signature = 'flambient:process
                            {--project= : Project name}
                            {--dir= : Image directory}
                            {--local : Local-only mode (skip cloud processing)}';

    protected $description = 'Process flambient photography workflow with ImageMagick and optional Imagen AI enhancement';

    public function handle(): int
    {
        // 1. Project setup: get project name, mode (full/local/upload), image directory
        $projectName = $this->option('project') ?: text(label: 'Project name', ...);
        $workflowMode = $this->option('local') ? 'local' : select(...);
        $imageDirectory = $uploadOnly ? $this->selectPreviousWorkflowOrCustom() : text(...);

        // 2. Image classification: choose EXIF field (e.g., Flash, MeteringMode) to distinguish ambient vs flash shots
        $strategy = ImageClassificationStrategy::from(select(...));
        $ambientValue = text(...);

        // 3. Configuration: set ImageMagick blending params, API key, output path
        $levelLow = '40%'; $levelHigh = '140%'; $gamma = '1.0';
        if (!$uploadOnly && confirm('Customize parameters?')) { /* prompt for values */ }

        // 4. Create WorkflowRun record in DB
        $run = WorkflowRun::create([...]);

        // 5. Simulate or execute workflow (MVP currently shows UI flow only)
        try {
            // In full version: call WorkflowOrchestrator
            // For now: show progress simulation
        } catch (\Throwable $e) { /* handle error */ }

        return 0;
    }

    private function selectPreviousWorkflowOrCustom(): string { /* list past outputs, allow custom path */ }
    private function selectImagenProfile(): string { /* fetch & select AI editing profile */ }
    private function selectEditPreset(): ImagenEditOptions { /* choose preset like 'flambient_real_estate' */ }
}
```

- **Type**: Laravel Artisan command (`extends Command`)
- **Signature**: `flambient:process [--project] [--dir] [--local]`
- **Side effects**: 
  - Interacts with user via CLI prompts
  - Creates a `WorkflowRun` database record
  - May create directories under `storage/flambient/`
  - May make HTTP calls to Imagen AI API (in full implementation)
- **Returns**: `int` (0 for success, non-zero for failure)
- **Interactive**: Yes — uses `Laravel\Prompts\*` functions for input
- **Modes**:
  - `full`: Local blending + Imagen AI enhancement
  - `local`: ImageMagick blending only
  - `upload`: Send previously processed images to Imagen AI only

---

### Example Usages

The primary way to use `FlambientProcessCommand` is via the command line:

```bash
php artisan flambient:process --project="client-house-123" --dir="/path/to/raw/images"
```

This launches an interactive session where the user:
- Chooses processing mode (full, local, upload)
- Selects how to classify ambient vs flash images (e.g., by `Flash` EXIF value)
- Optionally customizes ImageMagick blending levels
- Confirms configuration
- Starts processing

A real-world example from the project documentation shows integration with Imagen AI after local processing:

```php
144:155:/Users/davepeloso/Projects/flambient/IMAGEN_INTEGRATION.md
// In app/Console/Commands/FlambientProcessCommand.php

use App\Services\ImagenAI\ImagenClient;
use App\Services\ImagenAI\ImagenEditOptions;

protected function handle()
{
    // ... existing EXIF and ImageMagick processing ...

    // After local ImageMagick processing completes
    if (!$processOnly) {
        $client = new ImagenClient($apiKey);
        $project = $client->createProject($blendedImages, $profileKey);
        $client->pollUntilComplete($project);
        $client->downloadResults($project, $outputDir);
    }
}
```

**Usage in codebase**:
- Located in `app/Console/Commands/FlambientProcessCommand.php`
- Referenced in architecture docs as the **main workflow command**
- Part of a planned suite including `FlambientStatusCommand`, `FlambientResumeCommand`, etc.
- Central to the flambient photography pipeline — all user-initiated processing starts here

---

### Notes

- Despite its complexity, the current implementation is an **MVP simulation** — actual image processing is not yet executed. As noted in the code: `// In the full implementation, this would call WorkflowOrchestrator` (line 276).
- The command uses a **hybrid approach**: it supports both CLI arguments and interactive prompts, falling back to prompts when arguments are missing.
- It is designed for **resumable workflows** — the `WorkflowRun` model suggests future support for pausing and resuming long-running jobs, though not yet implemented.

---

### See Also

- `WorkflowRun`: Eloquent model that tracks the status and metadata of each processing run; created at the start of `handle()`
- `WorkflowOrchestrator`: Service intended to manage the actual workflow execution; referenced in comments but not yet implemented
- `ImagenClient`: Wrapper for Google's Imagen AI API; used in upload/full modes to enhance images
- `ImageClassificationStrategy`: Enum defining which EXIF fields can be used to classify images (e.g., `Flash`, `MeteringMode`)
- `WorkflowConfig`: Data transfer object holding configuration for the workflow; instantiated with user inputs
- `FlambientStatusCommand`: Companion command (planned) to check the status of a workflow run using its ID