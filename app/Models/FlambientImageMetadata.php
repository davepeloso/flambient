<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlambientImageMetadata extends Model
{
    protected $table = 'flambient_image_metadata';
    protected $primaryKey = 'image_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'image_id',
        'flash_status',
        'exposure_mode',
        'white_balance',
        'iso',
        'exposure_time',
        'aperture',
        'focal_length',
        'additional_metadata',
        'tag',
        'ignore'
    ];

    protected $casts = [
        'iso' => 'integer',
        'exposure_time' => 'decimal:6',
        'aperture' => 'decimal:2',
        'additional_metadata' => 'array',
        'ignore' => 'boolean'
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(FlambientImage::class, 'image_id');
    }
}
