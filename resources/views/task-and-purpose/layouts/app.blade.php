@extends('task-and-purpose.layouts.documentation')

@section('title', 'App Layout Documentation')

@section('documentation')
{{-- 
View: layouts/app.blade.php
Type: Base Layout Template
Route: N/A (Used by all views)
--}}

# App Layout Documentation

## Purpose
The app layout serves as the base template for all Flambient.io views, providing:
- Consistent terminal-inspired styling
- Common header and navigation
- Shared meta tags and assets
- CSRF protection
- Responsive design structure

## Tasks
1. Establish Base HTML Structure
   - Define DOCTYPE and language
   - Set viewport and character encoding
   - Include meta tags for SEO
   - Set up CSRF token

2. Implement Terminal Theme
   - Dark background (#1a1a1a)
   - Light text (#f0f0f0)
   - Accent color (#4a9eff)
   - Monospace font (Courier New)
   - Terminal-like borders and spacing

3. Create Navigation Structure
   - Logo/brand link
   - Main navigation items
   - Dynamic route highlighting
   - Mobile-responsive menu

4. Handle Asset Management
   - CSS inclusion
   - JavaScript loading
   - Font loading
   - Dynamic asset versioning

## Implementation Details

### Layout Structure
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <!-- Meta tags -->
        <!-- CSRF token -->
        <!-- Assets -->
    </head>
    <body>
        <div class="flambient-app" id="app-container-main">
            <header class="flambient-header" id="app-header-main">
                <!-- Navigation -->
            </header>

            <main class="flambient-main" id="app-content-main">
                @yield('content')
            </main>

            <footer class="flambient-footer" id="app-footer-main">
                <!-- Footer content -->
            </footer>
        </div>
    </body>
</html>
```

### CSS Classes Used (BEM Methodology)
```css
/* Layout */
.flambient-app
.flambient-header
.flambient-main
.flambient-footer

/* Navigation */
.flambient-nav
.flambient-nav__item
.flambient-nav__link
.flambient-nav__brand

/* Components */
.flambient-terminal-card
.flambient-button
.flambient-text

/* States */
.flambient-nav__link--active
.flambient-nav--mobile-open

/* Utilities */
.container
.mx-auto
.px-4
```

### Element IDs (following {category}-{type}-{purpose})
```html
app-container-main    <!-- Main app wrapper -->
app-header-main      <!-- Header container -->
app-nav-main         <!-- Navigation menu -->
app-content-main     <!-- Content area -->
app-footer-main      <!-- Footer container -->
```

### Blade Sections and Stacks
```blade
@section('content')      <!-- Main content -->
@section('title')        <!-- Page title -->
@stack('scripts')        <!-- JavaScript -->
@stack('styles')         <!-- Additional CSS -->
```

## Terminal Commands

### Development
```bash
# Clear view cache
php artisan view:clear

# Clear route cache
php artisan route:clear

# Clear application cache
php artisan cache:clear
```

### Asset Management
```bash
# Link storage
php artisan storage:link

# Clear compiled views
php artisan view:clear
```

## Dependencies
- `App\Constants\NamingConventions` (Style conventions)
- No external CSS frameworks
- Minimal JavaScript
- System fonts (Courier New)

## Notes
- All views must extend this layout
- Terminal theme is consistently applied
- CSS follows BEM methodology
- IDs follow {category}-{type}-{purpose} convention
- Minimal external dependencies
- CSRF protection on all forms
- Asset versioning for cache busting
@endsection
