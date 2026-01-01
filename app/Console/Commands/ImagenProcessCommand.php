<?php

namespace App\Console\Commands;

use App\Enums\ImagenJobStatus;
use App\Models\ImagenJob;
use App\Services\ImagenAI\ImagenClient;
use App\Services\ImagenAI\ImagenEditOptions;
use App\Services\ImagenAI\ImagenException;
use App\Services\ImagenAI\ImagenPhotographyType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

/**
 * Standalone Imagen AI Processing Command
 *
 * This command is completely independent of ImageMagick/Flambient workflows.
 * It can be used for any Imagen AI processing:
 * - RAW file processing (first pass)
 * - JPEG enhancement with window pull
 * - Product photography
 * - Portrait retouching
 * - Any other Imagen AI profile
 *
 * Features:
 * - Database tracking of all jobs
 * - Resume failed/interrupted jobs
 * - Multi-pass workflow support
 * - Progress tracking with status updates
 *
 * @example php artisan imagen:process --input="/path/to/images" --output="/path/to/output" --profile=309406
 * @example php artisan imagen:process --resume=abc123
 * @example php artisan imagen:process --list
 */
class ImagenProcessCommand extends Command
{
    protected $signature = 'imagen:process
        {--input= : Input directory containing images (RAW or JPEG)}
        {--output= : Output directory for processed images}
        {--profile= : Imagen AI profile key or name}
        {--project-name= : Custom project name for Imagen AI}
        {--type= : Photography type (real_estate, wedding, portrait, product, landscape, event)}
        {--window-pull : Enable window pull processing}
        {--crop : Enable auto-crop}
        {--perspective : Enable perspective correction}
        {--hdr : Enable HDR merge}
        {--pattern= : File pattern to match (comma-separated, e.g., *.jpg,*.cr2)}
        {--source=manual : Source type (manual, shortcut, flambient, product)}
        {--parent= : Parent job ID for multi-pass workflows}
        {--resume= : Resume a previous job by ID}
        {--list : List recent jobs}
        {--status= : Check status of a job by ID}
        {--dry-run : Show what would be processed without uploading}';

    protected $description = 'Process images through Imagen AI (standalone - no ImageMagick dependency)';

    private ImagenClient $client;
    private ?ImagenJob $job = null;

    public function handle(): int
    {
        // Handle list/status commands first (no client needed)
        if ($this->option('list')) {
            return $this->listJobs();
        }

        if ($this->option('status')) {
            return $this->showJobStatus($this->option('status'));
        }

        info('Imagen AI Processor');
        note('Upload images to Imagen AI for professional editing');
        $this->newLine();

        try {
            $this->client = new ImagenClient();
        } catch (ImagenException $e) {
            error("Failed to initialize Imagen client: {$e->getMessage()}");
            note('Make sure IMAGEN_AI_API_KEY is set in your .env file');
            return self::FAILURE;
        }

        // Handle resume
        if ($this->option('resume')) {
            return $this->resumeJob($this->option('resume'));
        }

        // ═══════════════════════════════════════════════════════
        // 1. GATHER INPUT PARAMETERS
        // ═══════════════════════════════════════════════════════

        $inputDirectory = $this->getInputDirectory();
        if (!$inputDirectory) {
            return self::FAILURE;
        }

        $images = $this->discoverImages($inputDirectory);
        if (empty($images)) {
            error("No images found in: {$inputDirectory}");
            note('Supported formats: JPEG, CR2, NEF, ARW, DNG');
            return self::FAILURE;
        }

        $outputDirectory = $this->getOutputDirectory($inputDirectory);
        $profileKey = $this->getProfile();
        $editOptions = $this->getEditOptions();
        $projectName = $this->getProjectName($inputDirectory);

        // ═══════════════════════════════════════════════════════
        // 2. CREATE JOB RECORD
        // ═══════════════════════════════════════════════════════

        $this->job = ImagenJob::createForWorkflow(
            projectName: $projectName,
            inputDirectory: $inputDirectory,
            outputDirectory: $outputDirectory,
            profileKey: $profileKey,
            editOptions: $editOptions->toArray(),
            fileManifest: $images,
            sourceType: $this->option('source') ?? 'manual',
            photographyType: $editOptions->photographyType?->value,
            parentJobId: $this->option('parent'),
        );

        info("Job created: {$this->job->id}");

        // ═══════════════════════════════════════════════════════
        // 3. SHOW CONFIGURATION SUMMARY
        // ═══════════════════════════════════════════════════════

        $this->showSummary($inputDirectory, $outputDirectory, $images, $profileKey, $editOptions, $projectName);

        if ($this->option('dry-run')) {
            warning('Dry run mode - no images will be uploaded');
            $this->job->markCancelled();
            return self::SUCCESS;
        }

        if (!confirm('Start processing?', default: true)) {
            info('Operation cancelled.');
            $this->job->markCancelled();
            return self::SUCCESS;
        }

        // ═══════════════════════════════════════════════════════
        // 4. EXECUTE IMAGEN WORKFLOW
        // ═══════════════════════════════════════════════════════

        return $this->executeFromStep(ImagenJobStatus::Uploading);
    }

    /**
     * Resume a previous job
     */
    private function resumeJob(string $jobId): int
    {
        // Allow partial ID matching
        $this->job = ImagenJob::where('id', 'like', "{$jobId}%")->first();

        if (!$this->job) {
            error("Job not found: {$jobId}");
            return self::FAILURE;
        }

        info("Resuming job: {$this->job->id}");
        note("Project: {$this->job->project_name}");

        // Sync status from Imagen API if we have a project UUID
        if ($this->job->project_uuid) {
            $this->syncStatusFromApi();
        }

        note("Status: {$this->job->status->label()}");
        note("Progress: {$this->job->uploaded_files}/{$this->job->total_files} uploaded");
        $this->newLine();

        if (!$this->job->canResume()) {
            error("Job cannot be resumed (status: {$this->job->status->label()})");
            return self::FAILURE;
        }

        // For failed jobs, ask if they want to retry with different profile/options
        if ($this->job->isFailed()) {
            warning("Previous error: {$this->job->error_message}");
            $this->newLine();

            $retryChoice = select(
                label: 'How would you like to retry?',
                options: [
                    'reselect' => 'Re-select profile and options (recommended for profile errors)',
                    'continue' => 'Continue from where it failed',
                    'cancel' => '← Cancel',
                ],
                hint: 'Profile errors require re-selecting the profile'
            );

            if ($retryChoice === 'cancel') {
                return self::SUCCESS;
            }

            if ($retryChoice === 'reselect') {
                return $this->retryWithNewProfile();
            }
        }

        if (!confirm('Resume this job?', default: true)) {
            return self::SUCCESS;
        }

        return $this->executeFromStep($this->job->status);
    }

    /**
     * Sync local job status with Imagen API
     */
    private function syncStatusFromApi(): void
    {
        try {
            $apiStatus = spin(
                callback: fn() => $this->client->getEditStatus($this->job->project_uuid),
                message: 'Checking status with Imagen AI...'
            );

            // Map API status to local status
            $statusLower = strtolower($apiStatus->status);

            if ($apiStatus->isComplete) {
                // Check if we already downloaded
                if ($this->job->downloaded_files > 0) {
                    $this->job->update(['status' => ImagenJobStatus::Completed]);
                } else {
                    $this->job->update(['status' => ImagenJobStatus::Downloading]);
                }
            } elseif ($apiStatus->isFailed) {
                $this->job->update([
                    'status' => ImagenJobStatus::Failed,
                    'error_message' => $apiStatus->message ?? 'Failed on Imagen AI',
                ]);
            } elseif (in_array($statusLower, ['pending', 'queued'])) {
                // Still pending on Imagen - might need to start editing
                if ($this->job->uploaded_files >= $this->job->total_files) {
                    $this->job->update(['status' => ImagenJobStatus::Processing]);
                }
            } elseif (in_array($statusLower, ['processing', 'editing', 'in_progress'])) {
                $this->job->update([
                    'status' => ImagenJobStatus::Processing,
                    'progress' => $apiStatus->progress,
                ]);
            } elseif (in_array($statusLower, ['exporting', 'export_pending'])) {
                $this->job->update(['status' => ImagenJobStatus::Exporting]);
            }

            $this->job->refresh();
        } catch (\Exception $e) {
            warning("Could not sync with API: {$e->getMessage()}");
            // Continue with local status
        }
    }

    /**
     * Retry a failed job with new profile/options selection
     */
    private function retryWithNewProfile(): int
    {
        info('Re-selecting profile and options...');
        $this->newLine();

        // Get new profile
        $profileKey = $this->getProfile();
        $editOptions = $this->getEditOptions();

        // Update job with new settings
        $this->job->update([
            'profile_key' => $profileKey,
            'edit_options' => $editOptions->toArray(),
            'photography_type' => $editOptions->photographyType?->value,
            'status' => ImagenJobStatus::Processing, // Reset to processing
            'error_message' => null,
        ]);

        // Show updated config
        $this->newLine();
        table(
            headers: ['Setting', 'Value'],
            rows: [
                ['Profile', $profileKey],
                ['Window Pull', $editOptions->windowPull ? 'Yes' : 'No'],
                ['Auto Crop', $editOptions->crop ? 'Yes' : 'No'],
                ['Photography Type', $editOptions->photographyType?->value ?? 'Auto'],
            ]
        );

        if (!confirm('Start processing with these settings?', default: true)) {
            return self::SUCCESS;
        }

        // Start from processing step (images already uploaded)
        return $this->executeFromStep(ImagenJobStatus::Processing);
    }

    /**
     * Execute workflow from a specific step
     */
    private function executeFromStep(ImagenJobStatus $fromStep): int
    {
        try {
            $this->job->markStarted();

            // Determine where to start based on status
            // Uses intentional fall-through to continue from the failed step
            switch ($fromStep) {
                case ImagenJobStatus::Pending:
                case ImagenJobStatus::Uploading:
                    $this->stepUpload();
                    // no break - intentional fall-through to continue workflow
                case ImagenJobStatus::Processing:
                    $this->stepProcess();
                    // no break - intentional fall-through
                case ImagenJobStatus::Exporting:
                    $this->stepExport();
                    // no break - intentional fall-through
                case ImagenJobStatus::Downloading:
                    $this->stepDownload();
                    break;
                default:
                    // Already complete or cancelled
                    break;
            }

            $this->showResults();
            return self::SUCCESS;

        } catch (ImagenException $e) {
            $this->job->markFailed($e->getMessage());
            error("Imagen AI error: {$e->getMessage()}");
            note("Job saved. Resume with: php artisan imagen:process --resume={$this->job->id}");
            return self::FAILURE;
        } catch (\Exception $e) {
            $this->job->markFailed($e->getMessage());
            error("Unexpected error: {$e->getMessage()}");
            note("Job saved. Resume with: php artisan imagen:process --resume={$this->job->id}");
            return self::FAILURE;
        }
    }

    /**
     * Step 1: Upload images
     */
    private function stepUpload(): void
    {
        info('Step 1/4: Uploading images...');

        // Create project if we don't have one
        if (!$this->job->project_uuid) {
            $project = spin(
                callback: fn() => $this->client->createProject($this->job->project_name),
                message: 'Creating Imagen project...'
            );
            $this->job->update(['project_uuid' => $project->uuid]);
            note("Project created: {$project->uuid}");
        } else {
            note("Using existing project: {$this->job->project_uuid}");
        }

        // Get files to upload (may be partial if resuming)
        $filesToUpload = $this->job->getPendingUploads();

        if (empty($filesToUpload)) {
            $filesToUpload = $this->job->file_manifest;
        }

        $uploadProgress = progress(
            label: 'Uploading',
            steps: count($filesToUpload)
        );

        $uploadProgress->start();
        $succeeded = $this->job->uploaded_files;
        $failed = [];

        $uploadResult = $this->client->uploadImages(
            projectUuid: $this->job->project_uuid,
            filePaths: $filesToUpload,
            progressCallback: function ($current, $total, $filename) use ($uploadProgress, &$succeeded) {
                $uploadProgress->advance();
                $succeeded++;
                $this->job->updateUploadProgress($succeeded);
            }
        );

        $uploadProgress->finish();

        if (!$uploadResult->isFullySuccessful()) {
            $failed = $uploadResult->failed;
            $this->job->update(['failed_uploads' => $failed]);
            warning("Some uploads failed: " . implode(', ', $failed));
        }

        $this->job->markUploadsComplete($this->job->project_uuid);
        note("Uploaded {$succeeded} of {$this->job->total_files} images");
        $this->newLine();
    }

    /**
     * Step 2: Start and monitor AI processing
     */
    private function stepProcess(): void
    {
        info('Step 2/4: AI Processing...');

        // Build edit options from stored config
        $editOptions = new ImagenEditOptions(
            crop: $this->job->edit_options['crop'] ?? false,
            windowPull: $this->job->edit_options['windowPull'] ?? false,
            perspectiveCorrection: $this->job->edit_options['perspectiveCorrection'] ?? false,
            hdrMerge: $this->job->edit_options['hdrMerge'] ?? false,
            photographyType: $this->job->photography_type
                ? ImagenPhotographyType::tryFrom($this->job->photography_type)
                : null
        );

        // Start editing
        spin(
            callback: fn() => $this->client->startEditing(
                projectUuid: $this->job->project_uuid,
                profileKey: $this->job->profile_key,
                options: $editOptions
            ),
            message: 'Starting AI processing...'
        );

        // Poll for completion
        $lastProgress = 0;
        $editStatus = $this->client->pollEditStatus(
            projectUuid: $this->job->project_uuid,
            progressCallback: function ($status) use (&$lastProgress) {
                if ($status->progress !== $lastProgress && $status->progress % 10 === 0) {
                    info("  Progress: {$status->progress}%");
                    $this->job->updateProcessingProgress($status->progress);
                    $lastProgress = $status->progress;
                }
            }
        );

        $this->job->markProcessingComplete();
        note('AI processing complete');
        $this->newLine();
    }

    /**
     * Step 3: Export to JPEG
     */
    private function stepExport(): void
    {
        info('Step 3/4: Exporting to JPEG...');

        spin(
            callback: function() {
                $this->client->exportProject($this->job->project_uuid);

                // Poll for export completion
                $this->client->pollExportStatus(
                    projectUuid: $this->job->project_uuid,
                    progressCallback: function ($status) {
                        // Silent polling
                    }
                );
            },
            message: 'Exporting...'
        );

        $this->job->markDownloading();
        note('Export complete');
        $this->newLine();
    }

    /**
     * Step 4: Download processed images
     */
    private function stepDownload(): void
    {
        info('Step 4/4: Downloading processed images...');

        $outputDirectory = $this->job->output_directory;

        // Create subdirectories
        $editedDir = "{$outputDirectory}/edited";
        $flambientDir = "{$outputDirectory}/flambient";

        if (!is_dir($editedDir)) {
            File::makeDirectory($editedDir, 0755, true);
        }
        if (!is_dir($flambientDir)) {
            File::makeDirectory($flambientDir, 0755, true);
        }

        // Get export links
        $exportLinks = spin(
            callback: fn() => $this->client->getExportLinks($this->job->project_uuid),
            message: 'Getting download links...'
        );

        if ($exportLinks->isEmpty()) {
            warning('No export links available yet, retrying...');
            sleep(10);
            $exportLinks = $this->client->getExportLinks($this->job->project_uuid);
        }

        $downloadProgress = progress(
            label: 'Downloading',
            steps: $exportLinks->count()
        );

        $downloadProgress->start();
        $downloadResult = $this->client->downloadFiles(
            downloadLinks: $exportLinks,
            outputDirectory: $editedDir,
            progressCallback: function ($current, $total, $filename) use ($downloadProgress) {
                $downloadProgress->advance();
            }
        );
        $downloadProgress->finish();

        // Also get XMP/ACR sidecars if available
        try {
            $downloadLinks = $this->client->getDownloadLinks($this->job->project_uuid);
            if ($downloadLinks->isNotEmpty()) {
                $this->client->downloadFiles(
                    downloadLinks: $downloadLinks,
                    outputDirectory: $flambientDir
                );
                note("Downloaded XMP/ACR sidecars to {$flambientDir}");
            }
        } catch (\Exception $e) {
            // XMP downloads are optional
        }

        $this->job->markComplete(count($downloadResult->succeeded));
        note("Downloaded " . count($downloadResult->succeeded) . " images to {$editedDir}");
        $this->newLine();
    }

    /**
     * List recent jobs
     */
    private function listJobs(): int
    {
        info('Recent Imagen Jobs');
        $this->newLine();

        $jobs = ImagenJob::orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        if ($jobs->isEmpty()) {
            note('No jobs found');
            return self::SUCCESS;
        }

        $rows = $jobs->map(fn($job) => [
            Str::limit($job->id, 8, ''),
            $job->project_name,
            $job->status->label(),
            "{$job->uploaded_files}/{$job->total_files}",
            $job->getDurationForHumans() ?? '-',
            $job->created_at->diffForHumans(),
        ])->toArray();

        table(
            headers: ['ID', 'Project', 'Status', 'Files', 'Duration', 'Created'],
            rows: $rows
        );

        $this->newLine();
        note('Resume a job with: php artisan imagen:process --resume=<id>');
        note('Check status with: php artisan imagen:process --status=<id>');

        return self::SUCCESS;
    }

    /**
     * Show detailed job status
     */
    private function showJobStatus(string $jobId): int
    {
        $job = ImagenJob::where('id', 'like', "{$jobId}%")->first();

        if (!$job) {
            error("Job not found: {$jobId}");
            return self::FAILURE;
        }

        info("Job Status: {$job->id}");
        $this->newLine();

        table(
            headers: ['Property', 'Value'],
            rows: [
                ['Project Name', $job->project_name],
                ['Imagen UUID', $job->project_uuid ?? 'Not created'],
                ['Status', $job->status->label()],
                ['Progress', "{$job->progress}%"],
                ['Input', $job->input_directory],
                ['Output', $job->output_directory],
                ['Profile', $job->profile_key],
                ['Photography Type', $job->photography_type ?? 'Auto'],
                ['Total Files', $job->total_files],
                ['Uploaded', $job->uploaded_files],
                ['Downloaded', $job->downloaded_files],
                ['Source', $job->source_type],
                ['Duration', $job->getDurationForHumans() ?? '-'],
                ['Started', $job->started_at?->toDateTimeString() ?? '-'],
                ['Completed', $job->completed_at?->toDateTimeString() ?? '-'],
            ]
        );

        if ($job->error_message) {
            $this->newLine();
            error("Error: {$job->error_message}");
        }

        if ($job->failed_uploads && count($job->failed_uploads) > 0) {
            $this->newLine();
            warning('Failed uploads:');
            foreach ($job->failed_uploads as $file) {
                $this->line("  - {$file}");
            }
        }

        if ($job->canResume()) {
            $this->newLine();
            note("Resume with: php artisan imagen:process --resume={$job->id}");
        }

        return self::SUCCESS;
    }

    /**
     * Display final results
     */
    private function showResults(): void
    {
        $this->newLine();
        outro('Imagen AI processing complete!');
        $this->newLine();

        $editedPath = "{$this->job->output_directory}/edited";
        $outputExists = is_dir($editedPath);

        $rows = [
            ['Job ID', $this->job->id],
            ['Project UUID', $this->job->project_uuid],
            ['Images Uploaded', $this->job->uploaded_files],
            ['Images Downloaded', $this->job->downloaded_files],
            ['Duration', $this->job->getDurationForHumans()],
        ];

        if ($outputExists) {
            $rows[] = ['Output Location', $editedPath];
        }

        table(
            headers: ['Result', 'Value'],
            rows: $rows
        );

        $this->newLine();

        if ($outputExists) {
            note("View your processed images at:\n  {$editedPath}");
        } elseif ($this->job->downloaded_files === 0) {
            warning("No images downloaded yet. You may need to resume the job to download results.");
        }

        note("Check job history with:\n  php artisan imagen:process --list");
    }

    // ═══════════════════════════════════════════════════════
    // INPUT HELPERS
    // ═══════════════════════════════════════════════════════

    private function getInputDirectory(): ?string
    {
        $input = $this->option('input');

        if (!$input) {
            $input = $this->browseForDirectory(
                label: 'Input directory',
                placeholder: '/path/to/images or public/shoot-name'
            );

            if (!$input) {
                return null;
            }
        }

        $input = $this->resolvePath($input);

        if (!is_dir($input)) {
            error("Directory does not exist: {$input}");
            return null;
        }

        return $input;
    }

    /**
     * Browse for a directory starting from public/ or enter manually
     */
    private function browseForDirectory(string $label, string $placeholder): ?string
    {
        $choice = select(
            label: $label,
            options: [
                'browse' => 'Browse public/ folder',
                'manual' => 'Enter path manually',
                'cancel' => '← Cancel',
            ],
            hint: 'Select how to choose the directory'
        );

        if ($choice === 'cancel') {
            return null;
        }

        if ($choice === 'manual') {
            return text(
                label: $label,
                placeholder: $placeholder,
                required: false,
                validate: fn($value) => $value ? $this->validateDirectory($value) : null
            );
        }

        // Browse public/ folder
        $publicPath = base_path('public');
        $directories = $this->getSubdirectories($publicPath);

        if (empty($directories)) {
            warning('No subdirectories found in public/');
            return text(
                label: $label,
                placeholder: $placeholder,
                required: false,
                validate: fn($value) => $value ? $this->validateDirectory($value) : null
            );
        }

        $options = [];
        foreach ($directories as $dir) {
            $relativePath = 'public/' . basename($dir);
            $imageCount = $this->countImagesInDirectory($dir);
            $options[$relativePath] = basename($dir) . ($imageCount > 0 ? " ({$imageCount} images)" : '');
        }
        $options['manual'] = '← Enter path manually';
        $options['cancel'] = '← Cancel';

        $selected = select(
            label: 'Select folder from public/',
            options: $options,
            scroll: 15
        );

        if ($selected === 'cancel') {
            return null;
        }

        if ($selected === 'manual') {
            return text(
                label: $label,
                placeholder: $placeholder,
                required: false,
                validate: fn($value) => $value ? $this->validateDirectory($value) : null
            );
        }

        return $selected;
    }

    /**
     * Get subdirectories of a path
     */
    private function getSubdirectories(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $directories = [];
        $items = scandir($path);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . '/' . $item;
            if (is_dir($fullPath) && !str_starts_with($item, '.')) {
                $directories[] = $fullPath;
            }
        }

        sort($directories);
        return $directories;
    }

    /**
     * Count images in a directory
     */
    private function countImagesInDirectory(string $directory): int
    {
        $extensions = ['jpg', 'jpeg', 'cr2', 'cr3', 'nef', 'arw', 'dng', 'raf'];
        $count = 0;

        foreach ($extensions as $ext) {
            $count += count(glob("{$directory}/*.{$ext}", GLOB_NOSORT));
            $count += count(glob("{$directory}/*." . strtoupper($ext), GLOB_NOSORT));
        }

        return $count;
    }

    private function getOutputDirectory(string $inputDirectory): string
    {
        $output = $this->option('output');

        if (!$output) {
            $defaultOutput = $this->suggestOutputDirectory($inputDirectory);

            $output = text(
                label: 'Output directory',
                placeholder: $defaultOutput,
                default: $defaultOutput,
                required: true
            );
        }

        $output = $this->resolvePath($output);

        if (!is_dir($output)) {
            File::makeDirectory($output, 0755, true);
            note("Created output directory: {$output}");
        }

        return $output;
    }

    private function getProfile(): string|int
    {
        $profile = $this->option('profile');

        if ($profile) {
            return is_numeric($profile) ? (int) $profile : $profile;
        }

        $profiles = spin(
            callback: fn() => $this->client->getProfiles(),
            message: 'Fetching available profiles...'
        );

        if ($profiles->isEmpty()) {
            warning('Could not fetch profiles, using default');
            return config('flambient.imagen.profile_key', 309406);
        }

        $options = $profiles->mapWithKeys(fn($p) => [
            $p->key => "{$p->name} ({$p->key})"
        ])->toArray();

        return select(
            label: 'Select Imagen AI profile',
            options: $options,
            default: array_key_first($options)
        );
    }

    private function getEditOptions(): ImagenEditOptions
    {
        $hasFlags = $this->option('window-pull')
            || $this->option('crop')
            || $this->option('perspective')
            || $this->option('hdr')
            || $this->option('type');

        if ($hasFlags) {
            return new ImagenEditOptions(
                crop: (bool) $this->option('crop'),
                windowPull: (bool) $this->option('window-pull'),
                perspectiveCorrection: (bool) $this->option('perspective'),
                hdrMerge: (bool) $this->option('hdr'),
                photographyType: $this->getPhotographyType()
            );
        }

        if (!confirm('Use default edit options?', default: true)) {
            $windowPull = confirm('Enable window pull?', default: false);
            $crop = confirm('Enable auto-crop?', default: false);
            $perspective = confirm('Enable perspective correction?', default: false);
            $hdr = confirm('Enable HDR merge?', default: false);

            $type = select(
                label: 'Photography type',
                options: [
                    '' => 'Auto-detect',
                    'REAL_ESTATE' => 'Real Estate',
                    'WEDDING' => 'Wedding',
                    'PORTRAIT' => 'Portrait',
                    'PRODUCT' => 'Product',
                    'LANDSCAPE' => 'Landscape',
                    'EVENT' => 'Event',
                ],
                default: ''
            );

            return new ImagenEditOptions(
                crop: $crop,
                windowPull: $windowPull,
                perspectiveCorrection: $perspective,
                hdrMerge: $hdr,
                photographyType: $type ? ImagenPhotographyType::tryFrom($type) : null
            );
        }

        return new ImagenEditOptions();
    }

    private function getPhotographyType(): ?ImagenPhotographyType
    {
        $type = $this->option('type');

        if (!$type) {
            return null;
        }

        return match(strtolower($type)) {
            'real_estate', 'realestate' => ImagenPhotographyType::REAL_ESTATE,
            'wedding' => ImagenPhotographyType::WEDDING,
            'portrait' => ImagenPhotographyType::PORTRAIT,
            'product' => ImagenPhotographyType::PRODUCT,
            'landscape' => ImagenPhotographyType::LANDSCAPE,
            'event' => ImagenPhotographyType::EVENT,
            default => null
        };
    }

    private function getProjectName(string $inputDirectory): string
    {
        $name = $this->option('project-name');

        if ($name) {
            return $name;
        }

        $dirName = basename($inputDirectory);
        $timestamp = now()->format('Ymd-His');

        return "{$dirName}-{$timestamp}";
    }

    private function discoverImages(string $directory): array
    {
        $patternOption = $this->option('pattern');

        // Default patterns for common image formats
        $defaultPatterns = [
            '*.jpg', '*.jpeg', '*.JPG', '*.JPEG',
            '*.cr2', '*.CR2', '*.cr3', '*.CR3',
            '*.nef', '*.NEF',
            '*.arw', '*.ARW',
            '*.dng', '*.DNG',
            '*.raf', '*.RAF',
        ];

        $patterns = $patternOption
            ? explode(',', $patternOption)
            : $defaultPatterns;

        $images = [];

        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);
            $found = glob("{$directory}/{$pattern}");
            $images = array_merge($images, $found);
        }

        $images = array_unique($images);
        sort($images);

        return $images;
    }

    private function showSummary(
        string $inputDirectory,
        string $outputDirectory,
        array $images,
        string|int $profileKey,
        ImagenEditOptions $editOptions,
        string $projectName
    ): void {
        $this->newLine();

        $fileTypes = $this->categorizeFiles($images);

        table(
            headers: ['Setting', 'Value'],
            rows: [
                ['Job ID', $this->job->id],
                ['Project Name', $projectName],
                ['Input Directory', $inputDirectory],
                ['Output Directory', $outputDirectory],
                ['Total Images', count($images)],
                ['File Types', implode(', ', array_keys($fileTypes))],
                ['Profile', $profileKey],
                ['Window Pull', $editOptions->windowPull ? 'Yes' : 'No'],
                ['Auto Crop', $editOptions->crop ? 'Yes' : 'No'],
                ['Perspective Correction', $editOptions->perspectiveCorrection ? 'Yes' : 'No'],
                ['HDR Merge', $editOptions->hdrMerge ? 'Yes' : 'No'],
                ['Photography Type', $editOptions->photographyType?->value ?? 'Auto'],
            ]
        );

        $this->newLine();
    }

    private function categorizeFiles(array $files): array
    {
        $types = [];

        foreach ($files as $file) {
            $ext = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
            $category = match($ext) {
                'JPG', 'JPEG' => 'JPEG',
                'CR2', 'CR3' => 'Canon RAW',
                'NEF' => 'Nikon RAW',
                'ARW' => 'Sony RAW',
                'DNG' => 'DNG',
                'RAF' => 'Fuji RAW',
                'ORF' => 'Olympus RAW',
                'RW2' => 'Panasonic RAW',
                default => $ext
            };

            $types[$category] = ($types[$category] ?? 0) + 1;
        }

        return $types;
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, '~')) {
            return str_replace('~', $_SERVER['HOME'] ?? '/Users/' . get_current_user(), $path);
        }

        return base_path($path);
    }

    private function suggestOutputDirectory(string $inputDirectory): string
    {
        if (str_contains($inputDirectory, '/public/')) {
            $dirName = basename($inputDirectory);
            return "storage/flambient/{$dirName}";
        }

        if (str_contains($inputDirectory, 'Pictures') || str_contains($inputDirectory, 'Capture')) {
            return "public/" . basename($inputDirectory);
        }

        return dirname($inputDirectory) . '/' . basename($inputDirectory) . '-edited';
    }

    private function validateDirectory(string $path): ?string
    {
        $resolved = $this->resolvePath($path);

        if (!is_dir($resolved)) {
            return "Directory does not exist: {$resolved}";
        }

        return null;
    }
}
