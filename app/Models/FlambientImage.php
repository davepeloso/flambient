<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FlambientImage extends Model
{
    use HasUuids;

    protected $table = 'flambient_images';
    protected $fillable = [
        'batch_id',
        'user_id',
        'filename',
        'storage_path',
        'file_size',
        'mime_type'
    ];

    public function metadata(): HasOne
    {
        return $this->hasOne(FlambientImageMetadata::class, 'image_id');
    }
}
