<?php

namespace App\Services\ImagenAI;

use Carbon\Carbon;

/**
 * Project created in Imagen AI.
 */
readonly class ImagenProject
{
    public function __construct(
        public string $uuid,
        public ?string $name,
        public Carbon $createdAt,
    ) {}
}

/**
 * Editing profile available in Imagen AI.
 */
readonly class ImagenProfile
{
    public function __construct(
        public string $key,
        public string $name,
        public ?string $profileType = null,
        public ?string $imageType = null,
        public ?string $photographyType = null,
    ) {}
}

/**
 * Temporary upload link for a file.
 */
readonly class ImagenUploadLink
{
    public function __construct(
        public string $filename,
        public string $uploadUrl,
    ) {}
}

/**
 * Result of uploading multiple files.
 */
readonly class ImagenUploadResult
{
    public function __construct(
        public string $projectUuid,
        public int $totalFiles,
        public array $succeeded,
        public array $failed,
    ) {}

    public function isFullySuccessful(): bool
    {
        return count($this->failed) === 0;
    }

    public function getSuccessRate(): float
    {
        if ($this->totalFiles === 0) {
            return 0.0;
        }
        return (count($this->succeeded) / $this->totalFiles) * 100;
    }
}

/**
 * Response from starting an edit.
 */
readonly class ImagenEditResponse
{
    public function __construct(
        public string $projectUuid,
        public string $status,
        public string $message,
    ) {}
}

/**
 * Current edit status.
 */
readonly class ImagenEditStatus
{
    public function __construct(
        public string $status,
        public int $progress,
        public ?string $message,
        public bool $isComplete,
        public bool $isFailed,
    ) {}
}

/**
 * Response from exporting a project.
 */
readonly class ImagenExportResponse
{
    public function __construct(
        public string $projectUuid,
        public string $status,
        public string $message,
    ) {}
}

/**
 * Download link for an edited file.
 */
readonly class ImagenDownloadLink
{
    public function __construct(
        public string $filename,
        public string $downloadUrl,
        public string $fileType, // 'xmp' or 'jpeg'
    ) {}
}

/**
 * Result of downloading multiple files.
 */
readonly class ImagenDownloadResult
{
    public function __construct(
        public int $totalFiles,
        public array $succeeded, // Array of file paths
        public array $failed,     // Array of filenames
    ) {}

    public function isFullySuccessful(): bool
    {
        return count($this->failed) === 0;
    }

    public function getSuccessRate(): float
    {
        if ($this->totalFiles === 0) {
            return 0.0;
        }
        return (count($this->succeeded) / $this->totalFiles) * 100;
    }
}

/**
 * Complete workflow result.
 */
readonly class ImagenWorkflowResult
{
    public function __construct(
        public string $projectUuid,
        public ImagenUploadResult $uploadResult,
        public ImagenEditStatus $editStatus,
        public ImagenDownloadResult $downloadResult,
    ) {}

    public function isFullySuccessful(): bool
    {
        return $this->uploadResult->isFullySuccessful()
            && $this->editStatus->isComplete
            && $this->downloadResult->isFullySuccessful();
    }
}

/**
 * Edit options for Imagen AI.
 */
readonly class ImagenEditOptions
{
    public function __construct(
        public bool $crop = false,
        public bool $portraitCrop = false,
        public bool $headshotCrop = false,
        public ?string $cropAspectRatio = null,
        public bool $hdrMerge = false,
        public bool $straighten = false,
        public bool $subjectMask = false,
        public ?ImagenPhotographyType $photographyType = null,
        public ?string $callbackUrl = null,
        public bool $smoothSkin = false,
        public bool $perspectiveCorrection = false,
        public bool $windowPull = true,
        public bool $skyReplacement = false,
        public ?int $skyReplacementTemplateId = null,
        public string $hdrOutputCompression = 'LOSSY', // LOSSY or LOSSLESS
    ) {}

    /**
     * Create from config preset array
     */
    public static function fromPreset(array $preset): self
    {
        // Map photography_type string to enum
        $photographyType = isset($preset['photography_type'])
            ? ImagenPhotographyType::tryFrom($preset['photography_type'])
            : null;

        return new self(
            crop: $preset['crop'] ?? false,
            portraitCrop: $preset['portrait_crop'] ?? false,
            headshotCrop: $preset['headshot_crop'] ?? false,
            cropAspectRatio: $preset['crop_aspect_ratio'] ?? null,
            hdrMerge: $preset['hdr_merge'] ?? false,
            straighten: $preset['straighten'] ?? false,
            subjectMask: $preset['subject_mask'] ?? false,
            photographyType: $photographyType,
            callbackUrl: $preset['callback_url'] ?? null,
            smoothSkin: $preset['smooth_skin'] ?? false,
            perspectiveCorrection: $preset['perspective_correction'] ?? false,
            windowPull: $preset['window_pull'] ?? true,
            skyReplacement: $preset['sky_replacement'] ?? false,
            skyReplacementTemplateId: $preset['sky_replacement_template_id'] ?? null,
            hdrOutputCompression: $preset['hdr_output_compression'] ?? 'LOSSY',
        );
    }

    /**
     * Convert to array for storage
     */
    public function toArray(): array
    {
        return [
            'crop' => $this->crop,
            'portraitCrop' => $this->portraitCrop,
            'headshotCrop' => $this->headshotCrop,
            'cropAspectRatio' => $this->cropAspectRatio,
            'hdrMerge' => $this->hdrMerge,
            'straighten' => $this->straighten,
            'subjectMask' => $this->subjectMask,
            'photographyType' => $this->photographyType?->value,
            'callbackUrl' => $this->callbackUrl,
            'smoothSkin' => $this->smoothSkin,
            'perspectiveCorrection' => $this->perspectiveCorrection,
            'windowPull' => $this->windowPull,
            'skyReplacement' => $this->skyReplacement,
            'skyReplacementTemplateId' => $this->skyReplacementTemplateId,
            'hdrOutputCompression' => $this->hdrOutputCompression,
        ];
    }
}

/**
 * Photography type optimization.
 */
enum ImagenPhotographyType: string
{
    case REAL_ESTATE = 'REAL_ESTATE';
    case WEDDING = 'WEDDING';
    case PORTRAIT = 'PORTRAIT';
    case PRODUCT = 'PRODUCT';
    case LANDSCAPE = 'LANDSCAPE';
    case EVENT = 'EVENT';

    public function label(): string
    {
        return match($this) {
            self::REAL_ESTATE => 'Real Estate',
            self::WEDDING => 'Wedding',
            self::PORTRAIT => 'Portrait',
            self::PRODUCT => 'Product',
            self::LANDSCAPE => 'Landscape',
            self::EVENT => 'Event',
        };
    }
}
