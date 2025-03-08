# FLAMBIENT.IO Technical Implementation

## Project Structure

```
flmbient/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── UploadController.php      # Handles image uploads and batch creation
│   │   │   ├── BatchController.php       # Manages batch viewing and delineation
│   │   │   ├── StackController.php       # Handles stack creation and processing
│   │   │   └── GalleryController.php     # Manages processed image display
│   │   └── Requests/
│   │       ├── UploadRequest.php         # Validates image uploads
│   │       └── StackRequest.php          # Validates stack creation
│   ├── Models/
│   │   ├── Image.php                     # Image metadata and relationships
│   │   ├── Stack.php                     # Stack grouping and relationships
│   │   └── Batch.php                     # Batch tracking and expiration
│   └── Services/
│       ├── ImageGroupingService.php      # Implements delineation field logic
│       ├── ProcessingService.php         # Handles ImageMagick operations
│       └── StorageService.php            # Manages file operations
├── database/
│   └── migrations/
│       ├── create_images_table.php       # Image metadata storage
│       ├── create_stacks_table.php       # Stack tracking
│       └── create_batches_table.php      # Batch management
└── resources/
    └── views/
        ├── layouts/
        │   └── app.blade.php             # Terminal-style base layout
        ├── upload.blade.php              # Upload interface
        ├── batch.blade.php               # Batch and delineation view
        └── gallery.blade.php             # Processed image display
```

## Database Schema

### images
```sql
CREATE TABLE images (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    batch_id UUID NOT NULL,
    stack_id BIGINT NULL,
    sequence_column INT NOT NULL,
    
    -- Delineation fields
    flash VARCHAR(255) NULL,          -- Delineation value: 'Off, Did not fire'
    exposure_mode VARCHAR(255) NULL,  -- Delineation value: 'Auto'
    white_balance VARCHAR(255) NULL,  -- Delineation value: 'Auto'
    iso INT NULL,                     -- Delineation value: 400
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (stack_id) REFERENCES stacks(id) ON DELETE SET NULL
);
```

### stacks
```sql
CREATE TABLE stacks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    batch_id UUID NOT NULL,
    delineation_field VARCHAR(255) NOT NULL,  -- Field used for transitions
    delineation_image_id BIGINT NULL,         -- First image with delineation value
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (delineation_image_id) REFERENCES images(id) ON DELETE SET NULL
);
```

### batches
```sql
CREATE TABLE batches (
    id UUID PRIMARY KEY,
    processing_script VARCHAR(255) NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'pending',
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Key Components

### ImageGroupingService
```php
class ImageGroupingService
{
    /**
     * Check if an image has a delineation value for the given field
     */
    private function hasDelineationValue(Image $image, string $field): bool 
    {
        return match($field) {
            'flash' => $image->flash === 'Off, Did not fire',
            'exposure_mode' => $image->exposure_mode === 'Auto',
            'white_balance' => $image->white_balance === 'Auto',
            'iso' => $image->iso === 400,
            default => false
        };
    }

    /**
     * Create stacks based on delineation field transitions
     */
    public function createStacks(Collection $images, string $field): Collection
    {
        $stacks = collect();
        $currentStack = null;
        $lastDelineationIndex = -1;

        foreach ($images->sortBy('sequence_column') as $index => $image) {
            $hasDelineationValue = $this->hasDelineationValue($image, $field);
            
            // Start new stack on delineation value
            if ($hasDelineationValue && $lastDelineationIndex !== -1) {
                $stacks->push($currentStack);
                $currentStack = collect();
            }
            
            $currentStack->push($image);
            if ($hasDelineationValue) {
                $lastDelineationIndex = $index;
            }
        }
        
        // Add final stack
        if ($currentStack->isNotEmpty()) {
            $stacks->push($currentStack);
        }

        return $stacks;
    }
}
```

### Routes
```php
Route::middleware(['web'])->group(function () {
    // Main upload interface
    Route::get('/', [UploadController::class, 'index'])->name('upload.index');
    Route::post('/', [UploadController::class, 'store'])->name('upload.store');

    // Batch operations
    Route::prefix('batch')->group(function () {
        // View batch and create stacks
        Route::get('{batchId}', [BatchController::class, 'show'])
            ->name('batch.show');
        Route::post('{batchId}/stacks', [StackController::class, 'store'])
            ->name('batch.stacks.store');

        // Gallery and download
        Route::get('{batchId}/gallery', [GalleryController::class, 'show'])
            ->name('gallery.show');
        Route::get('{batchId}/download', [GalleryController::class, 'download'])
            ->name('gallery.download');
    });
});
```

## Processing Pipeline

1. **Upload Phase**
   - Validate images (size, type)
   - Generate batch ID
   - Store images
   - Extract EXIF data
   - Record sequence

2. **Delineation Phase**
   - Display metadata table
   - User selects delineation field
   - Create stacks based on transitions
   - Preview stack groupings

3. **Processing Phase**
   - User selects processing script
   - Queue processing jobs
   - Track progress
   - Handle errors

4. **Delivery Phase**
   - Display processed images
   - Generate download links
   - Process payment
   - Track expiration

## Error Handling

```php
class ProcessingService
{
    public function processStack(Stack $stack)
    {
        try {
            // Process images in stack
            $result = $this->executeImageMagick($stack);
            
            if (!$result->successful()) {
                Log::error('Processing failed', [
                    'stack_id' => $stack->id,
                    'error' => $result->error
                ]);
                
                event(new StackProcessingFailed($stack));
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Processing exception', [
                'stack_id' => $stack->id,
                'exception' => $e->getMessage()
            ]);
            
            event(new StackProcessingFailed($stack));
            return false;
        }
    }
}
```

## Security Considerations

1. **File Upload**
   - Validate MIME types
   - Enforce size limits
   - Sanitize filenames
   - Use secure storage

2. **Processing**
   - Validate ImageMagick commands
   - Resource limits
   - Timeout handling
   - Error recovery

3. **Download**
   - Signed URLs
   - Expiration tracking
   - Rate limiting
   - Access control

## Performance Optimizations

1. **Upload**
   - Chunk large uploads
   - Parallel processing
   - Progress tracking

2. **Processing**
   - Queue background jobs
   - Batch operations
   - Resource management

3. **Storage**
   - Temporary storage
   - Automatic cleanup
   - Efficient file handling
