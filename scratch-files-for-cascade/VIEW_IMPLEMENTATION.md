# FLAMBIENT.IO View Implementation

## Base Layout (app.blade.php)

```blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FLAMBIENT.IO - @yield('title')</title>
    
    <!-- Terminal-style fonts -->
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        /* Terminal-like theme */
        :root {
            --bg-color: #1a1a1a;
            --text-color: #c3f2cb;
            --border-color: #333;
            --delineation-color: #6bff6b;
            --non-delineation-color: #ff6b6b;
        }

        body {
            font-family: 'JetBrains Mono', monospace;
            background: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .terminal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .prompt {
            color: var(--delineation-color);
        }

        /* Terminal-style form elements */
        input, select, button {
            font-family: 'JetBrains Mono', monospace;
            background: #333;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            padding: 0.5rem;
        }

        /* Terminal-style table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }

        th, td {
            text-align: left;
            padding: 0.5rem;
            border: 1px solid var(--border-color);
        }
    </style>
</head>
<body>
    <header class="terminal-header">
        <div class="prompt">$ flmbient @yield('command')</div>
    </header>

    <main class="terminal-content">
        @yield('content')
    </main>
</body>
</html>
```

## 1. Upload View (upload.blade.php)

```blade
@extends('layouts.app')

@section('title', 'Upload')
@section('command', 'upload')

@section('content')
<div class="upload-container">
    <div class="terminal-output">
        <p>Welcome to FLAMBIENT.IO</p>
        <p class="dim">Upload images for processing. Maximum 100 images, 8MB each.</p>
    </div>

    <form action="{{ route('upload.store') }}" method="post" enctype="multipart/form-data" class="terminal-form">
        @csrf
        <input type="hidden" name="batch_id" value="{{ $batchId }}">
        
        <div class="upload-zone" id="dropZone">
            <div class="prompt">$ flmbient upload --batch {{ $batchId }}</div>
            <input type="file" name="images[]" multiple accept="image/*" required>
        </div>

        <div class="file-list" id="fileList"></div>
        
        <button type="submit">Process Images</button>
    </form>
</div>

<style>
.upload-zone {
    border: 2px dashed var(--border-color);
    padding: 2rem;
    text-align: center;
    margin: 1rem 0;
}

.file-list {
    margin: 1rem 0;
    font-size: 0.9em;
}

.dim {
    opacity: 0.7;
}
</style>

<script>
// Minimal JavaScript for file list preview
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    const list = document.getElementById('fileList');
    list.innerHTML = Array.from(e.target.files)
        .map(file => `<div class="file-item">${file.name} (${(file.size/1024/1024).toFixed(2)}MB)</div>`)
        .join('');
});
</script>
```

## 2. Batch View (batch.blade.php)

```blade
@extends('layouts.app')

@section('title', 'Batch Details')
@section('command', 'batch {{ $batchId }}')

@section('content')
<div class="batch-container">
    <div class="terminal-output">
        <p>Batch contains {{ $images->count() }} images.</p>
        <p class="dim">Stack boundaries occur at delineation field transitions.</p>
    </div>

    <!-- Image List -->
    <table>
        <thead>
            <tr>
                <th>Sequence</th>
                <th>Field Values</th>
                <th>Delineation Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($images as $image)
            <tr>
                <td>{{ $image->sequence_column }}</td>
                <td>
                    <span class="field-value">flash: {{ $image->flash }}</span><br>
                    <span class="field-value">exposure_mode: {{ $image->exposure_mode }}</span><br>
                    <span class="field-value">white_balance: {{ $image->white_balance }}</span><br>
                    <span class="field-value">iso: {{ $image->iso }}</span>
                </td>
                <td class="delineation-status">
                    @if($image->hasDelineationValue($selectedField))
                        <span class="delineation">Delineation Value</span>
                    @else
                        <span class="non-delineation">Non-Delineation Value</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Stack Creation Form -->
    <form action="{{ route('batch.stacks.store', ['batchId' => $batchId]) }}" method="post" class="terminal-form">
        @csrf
        <div class="form-group">
            <label for="field">Select Delineation Field:</label>
            <select name="field" id="field" required>
                <option value="flash">Flash Status (Delineation Value: 'Off, Did not fire')</option>
                <option value="exposure_mode">Exposure Mode (Delineation Value: 'Auto')</option>
                <option value="white_balance">White Balance (Delineation Value: 'Auto')</option>
                <option value="iso">ISO (Delineation Value: 400)</option>
            </select>
            <p class="dim">Stack boundaries occur when the selected field transitions between delineation and non-delineation values.</p>
        </div>

        <button type="submit">Create Stacks</button>
    </form>
</div>

<style>
.delineation {
    color: var(--delineation-color);
}

.non-delineation {
    color: var(--non-delineation-color);
}

.field-value {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.9em;
}
</style>
```

## 3. Gallery View (gallery.blade.php)

```blade
@extends('layouts.app')

@section('title', 'Processed Images')
@section('command', 'gallery {{ $batchId }}')

@section('content')
<div class="gallery-container">
    <div class="terminal-header">
        <div class="prompt">$ flmbient gallery --batch {{ $batchId }}</div>
        <p class="dim">Expires in {{ $expiresIn }}</p>
    </div>

    <div class="gallery-grid">
        @foreach($stacks as $stack)
        <div class="stack-container">
            <div class="stack-header">
                <span class="prompt">Stack {{ $loop->iteration }}</span>
                <span class="dim">{{ $stack->images->count() }} images</span>
            </div>
            
            <div class="processed-image">
                <img src="{{ $stack->processedImageUrl }}" alt="Processed Stack {{ $loop->iteration }}">
                <div class="image-controls">
                    <a href="{{ route('gallery.download', ['stackId' => $stack->id]) }}" class="download-btn">
                        Download ($0.50)
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="batch-controls">
        <form action="{{ route('gallery.download.batch', ['batchId' => $batchId]) }}" method="post">
            @csrf
            <button type="submit" class="download-all-btn">
                Download All ({{ $stacks->count() }} images @ $0.30 each)
            </button>
        </form>
    </div>
</div>

<style>
.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    padding: 1rem;
}

.stack-container {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--border-color);
    padding: 1rem;
}

.processed-image img {
    width: 100%;
    height: auto;
    border: 1px solid var(--border-color);
}

.image-controls {
    margin-top: 1rem;
    text-align: center;
}

.download-btn, .download-all-btn {
    background: var(--delineation-color);
    color: var(--bg-color);
    padding: 0.5rem 1rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.batch-controls {
    margin-top: 2rem;
    text-align: center;
}
</style>
```

## Key Features

1. **Terminal-like Interface**
   - Command prompt style headers
   - Monospace font (JetBrains Mono)
   - Dark theme with green accents
   - Minimal animations

2. **Delineation Field Handling**
   - Clear indication of delineation values
   - Color-coded status indicators
   - Detailed field value display
   - Stack boundary explanations

3. **User Experience**
   - Drag & drop upload
   - Real-time file list
   - Clear status messages
   - Intuitive navigation

4. **Processing Feedback**
   - Stack grouping preview
   - Processing status
   - Error messages
   - Download options

## CSS Variables

```css
:root {
    /* Colors */
    --bg-color: #1a1a1a;
    --text-color: #c3f2cb;
    --border-color: #333;
    --delineation-color: #6bff6b;
    --non-delineation-color: #ff6b6b;
    --dim-opacity: 0.7;

    /* Spacing */
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 2rem;

    /* Typography */
    --font-mono: 'JetBrains Mono', monospace;
    --font-size-sm: 0.9em;
    --font-size-md: 1em;
    --font-size-lg: 1.2em;
}
```

## JavaScript Modules

1. **Upload Handler**
   - File validation
   - Progress tracking
   - Error handling

2. **Stack Preview**
   - Real-time grouping preview
   - Delineation field highlighting
   - Sequence validation

3. **Gallery Controls**
   - Image zoom
   - Download handling
   - Payment processing

## Responsive Design

```css
/* Mobile adjustments */
@media (max-width: 768px) {
    .gallery-grid {
        grid-template-columns: 1fr;
    }

    table {
        display: block;
        overflow-x: auto;
    }

    .terminal-header {
        font-size: var(--font-size-sm);
    }
}
```
