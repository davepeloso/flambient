<?php

namespace App\Models;

use App\Enums\ImagenJobStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Imagen AI Job Tracking Model
 *
 * Tracks all Imagen AI processing jobs for resume capability,
 * history, and multi-pass workflow coordination.
 *
 * @property string $id
 * @property string|null $project_uuid
 * @property string $project_name
 * @property string $input_directory
 * @property string $output_directory
 * @property string $profile_key
 * @property string|null $photography_type
 * @property array $edit_options
 * @property ImagenJobStatus $status
 * @property int $progress
 * @property string|null $error_message
 * @property int $total_files
 * @property int $uploaded_files
 * @property int $downloaded_files
 * @property array|null $failed_uploads
 * @property array|null $file_manifest
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $upload_completed_at
 * @property \Carbon\Carbon|null $processing_completed_at
 * @property \Carbon\Carbon|null $completed_at
 * @property string $source_type
 * @property string|null $parent_job_id
 * @property array|null $metadata
 */
class ImagenJob extends Model
{
    use HasUuids;

    protected $table = 'imagen_jobs';

    protected $fillable = [
        'project_uuid',
        'project_name',
        'input_directory',
        'output_directory',
        'profile_key',
        'photography_type',
        'edit_options',
        'status',
        'progress',
        'error_message',
        'total_files',
        'uploaded_files',
        'downloaded_files',
        'failed_uploads',
        'file_manifest',
        'started_at',
        'upload_completed_at',
        'processing_completed_at',
        'completed_at',
        'source_type',
        'parent_job_id',
        'metadata',
    ];

    protected $casts = [
        'edit_options' => 'array',
        'failed_uploads' => 'array',
        'file_manifest' => 'array',
        'metadata' => 'array',
        'status' => ImagenJobStatus::class,
        'started_at' => 'datetime',
        'upload_completed_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress' => 'integer',
        'total_files' => 'integer',
        'uploaded_files' => 'integer',
        'downloaded_files' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'progress' => 0,
        'total_files' => 0,
        'uploaded_files' => 0,
        'downloaded_files' => 0,
        'source_type' => 'manual',
    ];

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    /**
     * Parent job (for multi-pass workflows)
     */
    public function parentJob(): BelongsTo
    {
        return $this->belongsTo(ImagenJob::class, 'parent_job_id');
    }

    /**
     * Child jobs (subsequent passes in workflow)
     */
    public function childJobs(): HasMany
    {
        return $this->hasMany(ImagenJob::class, 'parent_job_id');
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    /**
     * Jobs that can be resumed
     */
    public function scopeResumable($query)
    {
        return $query->whereIn('status', [
            ImagenJobStatus::Pending->value,
            ImagenJobStatus::Uploading->value,
            ImagenJobStatus::Processing->value,
            ImagenJobStatus::Exporting->value,
            ImagenJobStatus::Downloading->value,
            ImagenJobStatus::Failed->value,
        ]);
    }

    /**
     * Jobs currently running
     */
    public function scopeRunning($query)
    {
        return $query->whereIn('status', [
            ImagenJobStatus::Uploading->value,
            ImagenJobStatus::Processing->value,
            ImagenJobStatus::Exporting->value,
            ImagenJobStatus::Downloading->value,
        ]);
    }

    /**
     * Completed jobs
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', ImagenJobStatus::Completed->value);
    }

    /**
     * Failed jobs
     */
    public function scopeFailed($query)
    {
        return $query->where('status', ImagenJobStatus::Failed->value);
    }

    /**
     * Jobs by source type
     */
    public function scopeBySource($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Recent jobs (last N days)
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ═══════════════════════════════════════════════════════
    // STATUS HELPERS
    // ═══════════════════════════════════════════════════════

    /**
     * Check if job can be resumed
     */
    public function canResume(): bool
    {
        return $this->status->isResumable();
    }

    /**
     * Check if job is complete
     */
    public function isComplete(): bool
    {
        return $this->status === ImagenJobStatus::Completed;
    }

    /**
     * Check if job failed
     */
    public function isFailed(): bool
    {
        return $this->status === ImagenJobStatus::Failed;
    }

    /**
     * Check if job is running
     */
    public function isRunning(): bool
    {
        return $this->status->isRunning();
    }

    /**
     * Get files that still need uploading
     */
    public function getPendingUploads(): array
    {
        if (empty($this->file_manifest)) {
            return [];
        }

        $failed = $this->failed_uploads ?? [];
        $uploaded = $this->uploaded_files;

        // If we have failed uploads, those are the ones to retry
        if (!empty($failed)) {
            return $failed;
        }

        // Otherwise, return remaining files from manifest
        return array_slice($this->file_manifest, $uploaded);
    }

    /**
     * Get upload progress percentage
     */
    public function getUploadProgress(): int
    {
        if ($this->total_files === 0) {
            return 0;
        }

        return (int) round(($this->uploaded_files / $this->total_files) * 100);
    }

    /**
     * Get total duration in seconds
     */
    public function getDurationSeconds(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get human-readable duration
     */
    public function getDurationForHumans(): ?string
    {
        $seconds = $this->getDurationSeconds();

        if ($seconds === null) {
            return null;
        }

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }

    // ═══════════════════════════════════════════════════════
    // STATUS TRANSITIONS
    // ═══════════════════════════════════════════════════════

    /**
     * Mark job as started
     */
    public function markStarted(): self
    {
        $this->update([
            'status' => ImagenJobStatus::Uploading,
            'started_at' => now(),
        ]);

        return $this;
    }

    /**
     * Update upload progress
     */
    public function updateUploadProgress(int $uploaded, array $failed = []): self
    {
        $this->update([
            'uploaded_files' => $uploaded,
            'failed_uploads' => $failed,
            'progress' => $this->getUploadProgress(),
        ]);

        return $this;
    }

    /**
     * Mark uploads complete
     */
    public function markUploadsComplete(string $projectUuid): self
    {
        $this->update([
            'project_uuid' => $projectUuid,
            'status' => ImagenJobStatus::Processing,
            'upload_completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Update processing progress
     */
    public function updateProcessingProgress(int $progress): self
    {
        $this->update([
            'progress' => $progress,
        ]);

        return $this;
    }

    /**
     * Mark processing complete
     */
    public function markProcessingComplete(): self
    {
        $this->update([
            'status' => ImagenJobStatus::Exporting,
            'processing_completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark as downloading
     */
    public function markDownloading(): self
    {
        $this->update([
            'status' => ImagenJobStatus::Downloading,
        ]);

        return $this;
    }

    /**
     * Mark job complete
     */
    public function markComplete(int $downloadedFiles): self
    {
        $this->update([
            'status' => ImagenJobStatus::Completed,
            'downloaded_files' => $downloadedFiles,
            'progress' => 100,
            'completed_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark job failed
     */
    public function markFailed(string $errorMessage): self
    {
        $this->update([
            'status' => ImagenJobStatus::Failed,
            'error_message' => $errorMessage,
        ]);

        return $this;
    }

    /**
     * Mark job cancelled
     */
    public function markCancelled(): self
    {
        $this->update([
            'status' => ImagenJobStatus::Cancelled,
        ]);

        return $this;
    }

    // ═══════════════════════════════════════════════════════
    // FACTORY METHODS
    // ═══════════════════════════════════════════════════════

    /**
     * Create a new job for a workflow
     */
    public static function createForWorkflow(
        string $projectName,
        string $inputDirectory,
        string $outputDirectory,
        string $profileKey,
        array $editOptions,
        array $fileManifest,
        string $sourceType = 'manual',
        ?string $photographyType = null,
        ?string $parentJobId = null,
    ): self {
        return self::create([
            'project_name' => $projectName,
            'input_directory' => $inputDirectory,
            'output_directory' => $outputDirectory,
            'profile_key' => $profileKey,
            'photography_type' => $photographyType,
            'edit_options' => $editOptions,
            'file_manifest' => $fileManifest,
            'total_files' => count($fileManifest),
            'source_type' => $sourceType,
            'parent_job_id' => $parentJobId,
        ]);
    }
}
