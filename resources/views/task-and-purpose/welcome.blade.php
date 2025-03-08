{{--
View: welcome.blade.php
Location: resources/views/task-and-purpose/welcome.blade.php
Purpose: Landing page and styling template for Flambient.io
--}}

@extends('task-and-purpose.layouts.documentation')

@section('title', 'Welcome View Documentation')

@section('documentation')
{{-- 
View: welcome.blade.php
Type: Landing Page & Style Guide
Route: / (home)
--}}

# Welcome View Documentation

## Purpose
The welcome view serves as both the landing page and the styling template for Flambient.io:
- Establishes the terminal-inspired design system
- Provides the base styling for all other views
- Introduces visitors to the service
- Explains the flambient photography method
- Shows pricing information
- Offers direct access to upload functionality
- Input for batch ID to reload previous batch where it left off.
    
## Core Design System
- Inputs i.e. for batch ID to reload previous batch where it left off. will have no boarder
an the same color as the background so it just looks like typing on the screen, if
and vill have a blinking cursor.
- Avoid using any external UI frameworks other that tailwind.
- The site will only need 7 colors.
    -background color: 
    -text color: dark, light, Solarized.


### Terminal-Inspired Theme Variables
```css
:root {
    --font-mono: 'Courier New', monospace;
    --color-bg-dark: #1a1a1a;
    --color-text-light: #f0f0f0;
    --color-accent: #4a9eff;
}
```

### Base Components & Classes
1. Layout Components
```css
.flambient-terminal-card {
    border: 1px solid var(--color-text-light);
    border-radius: 4px;
    padding: 2rem;
    margin: 2rem auto;
    max-width: 800px;
    background-color: var(--color-bg-dark);
}

.flambient-terminal-section {
    margin-top: 2rem;
    padding: 1rem 0;
    border-top: 1px solid rgba(240, 240, 240, 0.1);
}
```

2. Typography System
```css
.flambient-title-large {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-accent);
}

.flambient-subtitle {
    font-size: 1.5rem;
    color: var(--color-text-light);
}

.flambient-text {
    font-size: 1rem;
    color: var(--color-text-light);
}
```

3. Interactive Elements
```css
.flambient-button {
    padding: 0.75rem 1.5rem;
    border: 1px solid var(--color-accent);
    color: var(--color-accent);
    border-radius: 4px;
}

.flambient-list {
    list-style-type: none;
    padding-left: 1rem;
}

.flambient-list li::before {
    content: ">";
    color: var(--color-accent);
}
```

## Terminal Commands

### Cache Management
```bash
# Clear all application caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild all caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database Maintenance
```bash
# Clear all tables (to be implemented)
php artisan db:purge

# Remove expired data (to be implemented)
php artisan db:clean-expired

# Reset to fresh state (to be implemented)
php artisan db:refresh --seed
```

### Storage Management
```bash
# Clear processed images (to be implemented)
php artisan storage:clear-processed

# Remove expired files (to be implemented)
php artisan storage:clean-expired

# Clear all temporary files (to be implemented)
php artisan storage:clear-temp
```

## Implementation Details

### Layout Structure
```blade
@extends('layouts.app')
@section('content')
    <div class="flambient-terminal-card">
        <div class="flambient-terminal-content" id="home-content-main">
            <!-- Content sections -->
        </div>
    </div>
@endsection
```

### Element IDs (following {category}-{type}-{purpose})
```html
home-content-main     <!-- Main content container -->
home-section-workflow <!-- How it works section -->
home-section-pricing  <!-- Pricing information -->
home-button-upload    <!-- CTA button -->
```

### Routes Referenced
```php
route('home')   // Landing page (/)
route('upload') // Upload interface (/upload)
```

## Dependencies
- `layouts/app.blade.php` (Base layout)
- `App\Constants\NamingConventions` (Style conventions)
- No JavaScript dependencies (following minimal JS philosophy)
- No external UI frameworks

## Notes
- This view serves as the styling template for all other views
- All element IDs follow the {category}-{type}-{purpose} convention
- CSS classes follow BEM methodology with flambient- prefix
- Terminal-inspired interface is core to the application's identity
- View is stateless and requires no backend processing
- Part of the core user journey: Home → Upload → Process → Download
@endsection
