# Claude Code Implementation Prompt: Imagen Process Command

## Task Overview

Implement a standalone `imagen:process` Artisan command that provides independent Imagen AI processing with database tracking and resume capability. This command should be completely decoupled from the existing Flambient/ImageMagick workflow.

## Files to Create

### 1. Migration: `database/migrations/YYYY_MM_DD_HHMMSS_create_imagen_jobs_table.php`

Create a migration for tracking Imagen AI jobs with these columns:
- `id` (uuid, primary)
- `project_uuid` (string, nullable, unique) - Imagen's project UUID
- `project_name` (string)
- `input_directory` (string)
- `output_directory` (string)
- `profile_key` (string)
- `photography_type` (string, nullable)
- `edit_options` (json) - stores ImagenEditOptions
- `status` (string, default: 'pending') - pending/uploading/processing/exporting/downloading/completed/failed/cancelled
- `progress` (unsigned int, default: 0) - 0-100
- `error_message` (text, nullable)
- `total_files` (unsigned int)
- `uploaded_files` (unsigned int)
- `downloaded_files` (unsigned int)
- `failed_uploads` (json, nullable)
- `file_manifest` (json, nullable) - all file paths
- `started_at`, `upload_completed_at`, `processing_completed_at`, `completed_at` (timestamps)
- `source_type` (string, default: 'manual') - manual/shortcut/flambient/product
- `parent_job_id` (uuid, nullable) - for multi-pass workflows
- `metadata` (json, nullable)

Index on: status, project_name, created_at, (status + created_at)

### 2. Enum: `app/Enums/ImagenJobStatus.php`

```php
enum ImagenJobStatus: string
{
    case Pending = 'pending';
    case Uploading = 'uploading';
    case Processing = 'processing';
    case Exporting = 'exporting';
    case Downloading = 'downloading';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function isResumable(): bool;  // true for pending, uploading, processing, exporting, downloading, failed
    public function isTerminal(): bool;   // true for completed, failed, cancelled
    public function isRunning(): bool;    // true for uploading, processing, exporting, downloading
    public function label(): string;      // emoji + label like "📤 Uploading"
    public function next(): ?self;        // next status in workflow
}
```

### 3. Model: `app/Models/ImagenJob.php`

Eloquent model with:
- HasUuids trait
- Proper casts for json fields, enums, datetimes
- Relationships: `parentJob()`, `childJobs()`
- Scopes: `resumable()`, `running()`, `completed()`, `failed()`, `bySource()`, `recent()`
- Helpers: `canResume()`, `isComplete()`, `isFailed()`, `getPendingUploads()`, `getUploadProgress()`, `getDurationSeconds()`, `getDurationForHumans()`
- Status transitions: `markStarted()`, `updateUploadProgress()`, `markUploadsComplete()`, `markProcessingComplete()`, `markComplete()`, `markFailed()`, `markCancelled()`
- Factory: `createForWorkflow()` static method

### 4. Command: `app/Console/Commands/ImagenProcessCommand.php`

```php
protected $signature = 'imagen:process
    {--input= : Input directory containing images (RAW or JPEG)}
    {--output= : Output directory for processed images}
    {--profile= : Imagen AI profile key}
    {--project-name= : Custom project name}
    {--type= : Photography type (real_estate, wedding, portrait, product, landscape, event)}
    {--window-pull : Enable window pull}
    {--crop : Enable auto-crop}
    {--perspective : Enable perspective correction}
    {--hdr : Enable HDR merge}
    {--pattern=*.jpg,*.jpeg,*.JPG,*.JPEG,*.cr2,*.CR2,*.nef,*.NEF,*.arw,*.ARW,*.dng,*.DNG : File patterns}
    {--source=manual : Source type}
    {--parent= : Parent job ID for multi-pass}
    {--resume= : Resume job by ID}
    {--list : List recent jobs}
    {--status= : Check job status}
    {--dry-run : Preview without uploading}';
```

**Core Features:**

1. **New Job Flow:**
   - Gather input parameters (interactive or CLI)
   - Discover images in input directory
   - Create ImagenJob record
   - Show summary table
   - Execute 4-step workflow: Upload → Process → Export → Download
   - Track progress in database throughout

2. **Resume Flow (`--resume=<id>`):**
   - Load job by ID (support partial ID matching)
   - Validate job is resumable
   - Determine which step to resume from based on status
   - Continue workflow from that step

3. **List Jobs (`--list`):**
   - Show table of recent 20 jobs
   - Columns: ID (truncated), Project, Status, Files, Duration, Created

4. **Job Status (`--status=<id>`):**
   - Show detailed job info in table format
   - Display any errors or failed uploads
   - Show resume command if applicable

**Workflow Steps:**

```php
private function stepUpload(): void
{
    // Create project if needed (check job->project_uuid)
    // Get pending uploads (support resume with partial uploads)
    // Upload with progress callback updating job record
    // Mark uploads complete
}

private function stepProcess(): void
{
    // Start editing with stored edit_options
    // Poll with progress callback updating job record
    // Mark processing complete
}

private function stepExport(): void
{
    // Export project to JPEG
    // Mark as downloading
}

private function stepDownload(): void
{
    // Create output directories (edited/, flambient/)
    // Download export links to edited/
    // Optionally download XMP/ACR to flambient/
    // Mark job complete
}
```

## Integration Points

The command uses the existing `ImagenClient` service at `app/Services/ImagenAI/ImagenClient.php`. Key methods:
- `createProject(string $name): ImagenProject`
- `uploadImages(string $projectUuid, array $filePaths, ?callable $progressCallback): ImagenUploadResult`
- `startEditing(string $projectUuid, string|int $profileKey, ?ImagenEditOptions $options): ImagenEditResponse`
- `pollEditStatus(string $projectUuid, ?callable $progressCallback): ImagenEditStatus`
- `exportProject(string $projectUuid): void`
- `getExportLinks(string $projectUuid): Collection<ImagenDownloadLink>`
- `downloadFiles(Collection $links, string $outputDirectory, ?callable $progressCallback): ImagenDownloadResult`

## Usage Examples

```bash
# Interactive mode
php artisan imagen:process

# Full CLI mode
php artisan imagen:process \
  --input="/Users/dave/Pictures/ProFoto/Capture" \
  --output="public/995-Golden-Crest" \
  --profile=309406 \
  --type=real_estate

# With window pull (second pass)
php artisan imagen:process \
  --input="storage/flambient/995-Golden-Crest/flambient" \
  --output="storage/flambient/995-Golden-Crest" \
  --window-pull \
  --type=real_estate \
  --source=shortcut \
  --parent=<first-job-id>

# List jobs
php artisan imagen:process --list

# Check status
php artisan imagen:process --status=abc123

# Resume failed job
php artisan imagen:process --resume=abc123
```

## Output Structure

After completion:
```
{output_directory}/
├── edited/           # Flattened JPEGs (final deliverables)
│   ├── image_01.jpg
│   └── ...
└── flambient/        # JPEGs with ACR sidecars (optional)
    ├── image_01.jpg
    ├── image_01.acr
    └── ...
```

## Testing Checklist

- [ ] New job creates database record
- [ ] Interactive prompts work correctly
- [ ] CLI flags override prompts
- [ ] Profile selection fetches from API
- [ ] Upload progress updates database
- [ ] Processing progress updates database  
- [ ] Failed upload is recorded and can resume
- [ ] `--list` shows recent jobs
- [ ] `--status` shows job details
- [ ] `--resume` continues from correct step
- [ ] `--dry-run` doesn't upload
- [ ] Multi-pass workflow links parent/child jobs
- [ ] Error handling saves error message to database

## Notes

- Use Laravel Prompts for all interactive UI
- Support partial ID matching for --resume and --status
- Progress callbacks should update database AND show UI feedback
- The command should be completely independent of FlambientProcessCommand
- Photography types: real_estate, wedding, portrait, product, landscape, event
