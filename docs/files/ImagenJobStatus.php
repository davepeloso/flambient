<?php

namespace App\Enums;

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

    /**
     * Check if job can be resumed from this status
     */
    public function isResumable(): bool
    {
        return match($this) {
            self::Pending,
            self::Uploading,
            self::Processing,
            self::Exporting,
            self::Downloading,
            self::Failed => true,
            self::Completed,
            self::Cancelled => false,
        };
    }

    /**
     * Check if job is in a terminal state
     */
    public function isTerminal(): bool
    {
        return match($this) {
            self::Completed,
            self::Failed,
            self::Cancelled => true,
            default => false,
        };
    }

    /**
     * Check if job is actively running
     */
    public function isRunning(): bool
    {
        return match($this) {
            self::Uploading,
            self::Processing,
            self::Exporting,
            self::Downloading => true,
            default => false,
        };
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::Pending => '⏳ Pending',
            self::Uploading => '📤 Uploading',
            self::Processing => '🤖 Processing',
            self::Exporting => '📦 Exporting',
            self::Downloading => '📥 Downloading',
            self::Completed => '✅ Completed',
            self::Failed => '❌ Failed',
            self::Cancelled => '🚫 Cancelled',
        };
    }

    /**
     * Get the next logical status in workflow
     */
    public function next(): ?self
    {
        return match($this) {
            self::Pending => self::Uploading,
            self::Uploading => self::Processing,
            self::Processing => self::Exporting,
            self::Exporting => self::Downloading,
            self::Downloading => self::Completed,
            default => null,
        };
    }
}
