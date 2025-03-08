@extends('task-and-purpose.layouts.documentation')

@section('title', 'Gallery View Documentation')

@section('documentation')
{{-- 
View: gallery.blade.php
Type: Gallery Display
Route: /batch/{id}/gallery (batch.gallery)
--}}

# Gallery View Documentation

## Purpose
The gallery view displays processed flambient images for a specific batch, allowing users to:
- Preview their processed images before purchase
- Review the results of the flambient processing
- Select images for download
- Proceed to payment

## Tasks
1. Display Processed Images
   - Grid layout of processed flambients
   - Preview functionality
   - Image metadata display
   - Processing status indicators

2. Manage Image Selection
   - Select/deselect images
   - Batch selection options
   - Update pricing based on selection
   - Display image count

3. Handle Pricing Display
   - Show per-image price ($0.50 individual)
   - Show bulk price ($0.30 per image)
   - Calculate total based on selection
   - Display savings for bulk orders

4. Manage Expiration
   - Show 72-hour countdown
   - Display expiration date/time
   - Warning for approaching expiration
   - Clear expired images status

## Implementation Details

### Layout Structure
```blade
@extends('layouts.app')
@section('content')
    <div class="flambient-terminal-card">
        <div class="flambient-gallery" id="gallery-container-main">
            <!-- Gallery grid -->
            <!-- Selection controls -->
            <!-- Pricing summary -->
            <!-- Action buttons -->
        </div>
    </div>
@endsection
```

### CSS Classes Used (BEM Methodology)
```css
/* Layout */
.flambient-terminal-card
.flambient-gallery
.flambient-gallery__grid

/* Components */
.flambient-gallery__item
.flambient-gallery__controls
.flambient-gallery__summary
.flambient-button
.flambient-checkbox

/* States */
.flambient-gallery__item--selected
.flambient-gallery__item--processing
.flambient-gallery__item--error

/* Utilities */
.mt-4
.grid-cols-3
```

### Element IDs (following {category}-{type}-{purpose})
```html
gallery-container-main      <!-- Main gallery container -->
gallery-grid-images        <!-- Image grid container -->
gallery-controls-select    <!-- Selection controls -->
gallery-summary-pricing    <!-- Price calculation -->
gallery-button-download    <!-- Download action -->
gallery-timer-expiration  <!-- Expiration countdown -->
```

### Routes Referenced
```php
route('batch.gallery')   // Gallery view (/batch/{id}/gallery)
route('batch.download')  // Download page (/batch/{id}/download)
route('batch.select')    // Image selection endpoint
```

## Terminal Commands

### Development
```bash
# Start local development server
php artisan serve

# Clear view cache
php artisan view:clear

# Clear image cache
php artisan storage:clear-processed
```

### Testing
```bash
# Run gallery view tests
php artisan test --filter=GalleryViewTest

# Test image processing
php artisan test --filter=ImageProcessingTest
```

## Dependencies
- `layouts/app.blade.php` (Base layout)
- `App\Constants\NamingConventions` (Style conventions)
- `App\Services\ImageProcessingService` (Status checks)
- `App\Services\PricingService` (Price calculations)

## Notes
- Grid layout adapts to screen size
- Images load progressively as they're processed
- Selection state persists in session
- Pricing updates in real-time
- 72-hour expiration enforced server-side
- Part of core user journey: Upload → Process → Gallery → Download
@endsection
