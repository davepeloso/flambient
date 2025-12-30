# Standalone Imagen AI Command

## Overview

`ImagenProcessCommand` is a **completely standalone** command for Imagen AI processing. It has zero dependencies on ImageMagick, Flambient workflows, or any other processing pipeline.

**Key Features:**
- ✅ Database tracking of all jobs
- ✅ Resume failed/interrupted jobs
- ✅ Multi-pass workflow support (parent/child jobs)
- ✅ Progress tracking with live UI updates
- ✅ Interactive and CLI modes

## Installation

```bash
# 1. Copy files to your Laravel project
cp ImagenProcessCommandV2.php app/Console/Commands/ImagenProcessCommand.php
cp ImagenJob.php app/Models/
cp ImagenJobStatus.php app/Enums/
cp 2024_01_01_000000_create_imagen_jobs_table.php database/migrations/

# 2. Run migration
php artisan migrate
```

## Usage

### Interactive Mode
```bash
php artisan imagen:process
```

### Command Line Options
```bash
php artisan imagen:process \
  --input="/path/to/images" \
  --output="/path/to/output" \
  --profile=309406 \
  --window-pull \
  --type=real_estate
```

### Job Management
```bash
# List recent jobs
php artisan imagen:process --list

# Check job status (supports partial ID)
php artisan imagen:process --status=abc123

# Resume a failed/interrupted job
php artisan imagen:process --resume=abc123
```

### All Options

| Option | Description | Example |
|--------|-------------|---------|
| `--input` | Input directory (RAW or JPEG) | `--input="public/shoot-name"` |
| `--output` | Output directory | `--output="storage/edited"` |
| `--profile` | Imagen AI profile key | `--profile=309406` |
| `--project-name` | Custom project name | `--project-name="Golden Crest"` |
| `--type` | Photography type | `--type=real_estate` |
| `--window-pull` | Enable window pull | `--window-pull` |
| `--crop` | Enable auto-crop | `--crop` |
| `--perspective` | Enable perspective correction | `--perspective` |
| `--hdr` | Enable HDR merge | `--hdr` |
| `--pattern` | File pattern to match | `--pattern="*.CR2,*.jpg"` |
| `--source` | Source type for tracking | `--source=shortcut` |
| `--parent` | Parent job ID (multi-pass) | `--parent=abc-123` |
| `--resume` | Resume job by ID | `--resume=abc123` |
| `--list` | List recent jobs | `--list` |
| `--status` | Check job status | `--status=abc123` |
| `--dry-run` | Preview without uploading | `--dry-run` |

### Photography Types
- `real_estate` - Real Estate
- `wedding` - Wedding
- `portrait` - Portrait
- `product` - Product
- `landscape` - Landscape
- `event` - Event

### Source Types (for tracking)
- `manual` - Direct command execution (default)
- `shortcut` - Imagen Shortcut workflow (RAW → Imagen → ImageMagick → Imagen)
- `flambient` - Called from flambient:process
- `product` - Called from product:process

---

## Database Tracking

All jobs are tracked in the `imagen_jobs` table with full state management.

### Job Statuses

| Status | Description | Resumable? |
|--------|-------------|------------|
| ⏳ Pending | Job created, not started | ✅ |
| 📤 Uploading | Uploading images to Imagen | ✅ |
| 🤖 Processing | AI processing in progress | ✅ |
| 📦 Exporting | Exporting to JPEG | ✅ |
| 📥 Downloading | Downloading processed images | ✅ |
| ✅ Completed | Successfully finished | ❌ |
| ❌ Failed | Error occurred | ✅ |
| 🚫 Cancelled | User cancelled | ❌ |

### Resume Workflow

If a job fails or is interrupted:

```bash
# Check what happened
php artisan imagen:process --status=abc123

# Resume from where it left off
php artisan imagen:process --resume=abc123
```

The command automatically detects which step to resume from based on the job status.

### Multi-Pass Workflow Tracking

Link related jobs together using `--parent`:

```bash
# First pass (RAW processing)
php artisan imagen:process \
  --input="/path/to/raw" \
  --output="public/shoot" \
  --source=shortcut
# Returns: Job ID = abc123

# Second pass (after ImageMagick)
php artisan imagen:process \
  --input="storage/flambient/shoot/flambient" \
  --output="storage/flambient/shoot" \
  --window-pull \
  --source=shortcut \
  --parent=abc123
```

---

## Decoupled Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                 ImagenClient (Service Layer)                │
│              app/Services/ImagenAI/ImagenClient.php         │
│                                                             │
│   createProject() → uploadImages() → startEditing()         │
│   pollEditStatus() → exportProject() → downloadFiles()      │
└─────────────────────────────────────────────────────────────┘
                              ▲
                              │ uses
          ┌───────────────────┼───────────────────┐
          │                   │                   │
┌─────────┴─────────┐ ┌───────┴───────┐ ┌────────┴────────┐
│ imagen:process    │ │ flambient:    │ │ product:process │
│                   │ │ process       │ │ (future)        │
│ Standalone AI     │ │               │ │                 │
│ processing for    │ │ ImageMagick + │ │ Product photo   │
│ any images        │ │ Imagen combo  │ │ workflow        │
└───────────────────┘ └───────────────┘ └─────────────────┘
```

Each command is **independent** and can use `ImagenClient` however it needs.

---

## Your Workflows

### Current Workflow (with Capture One)
```bash
# 1. Shoot RAW
# 2. Edit in Capture One
# 3. Export JPEGs to public/{{job-name}}

# 4. Process with ImageMagick
php artisan flambient:process \
  --project="995-Golden-Crest" \
  --dir="public/995-Golden-Crest" \
  --local

# 5. Send to Imagen with window pull
php artisan imagen:process \
  --input="storage/flambient/995-Golden-Crest/flambient" \
  --output="storage/flambient/995-Golden-Crest" \
  --window-pull \
  --type=real_estate

# 6. Download complete!
```

### Imagen Shortcut (No Capture One)
```bash
# 1. Shoot RAW

# 2. Pass 1: RAW → Imagen (basic edit, no window pull)
php artisan imagen:process \
  --input="/Users/davepeloso/Pictures/ProFoto/Capture" \
  --output="public/995-Golden-Crest" \
  --profile="RAW-BASE-PROFILE" \
  --type=real_estate

# 3. Process with ImageMagick
php artisan flambient:process \
  --project="995-Golden-Crest" \
  --dir="public/995-Golden-Crest" \
  --local

# 4. Pass 2: Imagen with window pull
php artisan imagen:process \
  --input="storage/flambient/995-Golden-Crest/flambient" \
  --output="storage/flambient/995-Golden-Crest" \
  --window-pull \
  --type=real_estate

# 5. Download complete!
```

### Direct Imagen (No ImageMagick)
```bash
# For jobs that don't need flambient blending
php artisan imagen:process \
  --input="public/headshots" \
  --output="storage/headshots-edited" \
  --profile="PORTRAIT-PROFILE" \
  --type=portrait
```

---

## Future Commands

The same pattern works for any workflow:

### `product:process` (Future)
```php
class ProductProcessCommand extends Command
{
    protected $signature = 'product:process {--input=} {--output=}';
    
    public function handle()
    {
        // 1. Background removal (ImageMagick or AI)
        // 2. Color correction
        // 3. Shadow generation
        // 4. Send to Imagen for final polish
        
        $client = new ImagenClient();
        $client->quickEdit(
            filePaths: $processedImages,
            profileKey: 'PRODUCT-PROFILE',
            outputDirectory: $output,
            editOptions: new ImagenEditOptions(
                crop: true,
                photographyType: ImagenPhotographyType::PRODUCT
            )
        );
    }
}
```

### `portrait:process` (Future)
```php
class PortraitProcessCommand extends Command
{
    // Headshot-specific workflow
    // - Face detection
    // - Skin retouching presets
    // - Imagen enhancement
}
```

### `batch:process` (Future)
```php
class BatchProcessCommand extends Command
{
    // Process multiple shoots at once
    // - Queue-based processing
    // - Email notifications
    // - Dashboard integration
}
```

---

## Output Structure

After `imagen:process` completes:

```
storage/flambient/995-Golden-Crest/
├── edited/                    # ← Flattened JPEGs (ready to deliver)
│   ├── flambient_01.jpg
│   ├── flambient_02.jpg
│   └── ...
├── flambient/                 # ← JPEGs with ACR sidecars (if available)
│   ├── flambient_01.jpg
│   ├── flambient_01.acr
│   └── ...
├── metadata/
├── scripts/
└── temp/
```

The `edited/` folder contains the final deliverables - no ACR files, just clean JPEGs.

---

## Configuration

Required in `.env`:
```env
IMAGEN_AI_API_KEY=your_api_key_here
IMAGEN_PROFILE_KEY=309406          # Default profile
IMAGEN_API_BASE_URL=https://api-beta.imagen-ai.com/v1
```

Optional:
```env
IMAGEN_TIMEOUT=30
IMAGEN_POLL_INTERVAL=30
IMAGEN_POLL_MAX_ATTEMPTS=240
```
