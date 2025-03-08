<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Flambient.io') }}</title>

    <!-- Minimal CSS for terminal-like interface -->
    <style>
        /* Reset and base styles */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* Custom properties */
        :root {
            --bg-color: #1a1a1a;
            --text-color: #f0f0f0;
            --accent-color: #4a9eff;
            --border-color: #333;
            --font-mono: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
            --base-font-size: 0.55rem;
        }

        /* Core layout */
        .flambient {
            font-family: var(--font-mono);
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-size: var(--base-font-size);
            line-height: 1.6;
        }

        /* Header/Nav */
        .flambient-header {
            background-color: var(--bg-color);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .flambient-title {
            font-size: calc(var(--base-font-size) * 2.2);
            color: var(--accent-color);
            text-decoration: none;
        }

        .flambient-nav {
            display: flex;
            gap: 1.5rem;
        }

        .flambient-nav a {
            color: var(--text-color);
            text-decoration: none;
            padding: 0.5rem;
            transition: color 0.2s ease;
            font-size: calc(var(--base-font-size) * 2);
        }

        .flambient-nav a:hover {
            color: var(--accent-color);
        }

        /* Main Container */
        .flambient-main {
            flex: 1;
            padding: 2rem;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        /* Terminal Card */
        .flambient-terminal-card {
            background-color: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 2rem;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .flambient-terminal-content {
            line-height: 1.6;
        }

        .flambient-terminal-section {
            border-top: 1px solid var(--border-color);
            padding-top: 1.5rem;
        }

        /* Typography */
        .flambient-title-large {
            font-size: calc(var(--base-font-size) * 3.6);
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .flambient-subtitle {
            font-size: calc(var(--base-font-size) * 2.5);
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .flambient-text {
            font-size: calc(var(--base-font-size) * 2);
            line-height: 1.6;
        }

        /* Lists */
        .flambient-list {
            list-style-type: none;
            margin: 1rem 0;
        }

        .flambient-list li {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            font-size: calc(var(--base-font-size) * 2);
        }

        .flambient-list li::before {
            content: '>';
            color: var(--accent-color);
            position: absolute;
            left: 0;
        }

        /* Buttons */
        .flambient-button {
            display: inline-block;
            text-decoration: none;
            background-color: transparent;
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            font-family: var(--font-mono);
            font-size: calc(var(--base-font-size) * 2);
            transition: all 0.2s ease;
        }

        .flambient-button:hover {
            background-color: var(--accent-color);
            color: var(--bg-color);
        }

        /* Footer */
        .flambient-footer {
            text-align: center;
            background-color: var(--bg-color);
            border-top: 1px solid var(--border-color);
            padding: 1rem;
            font-size: calc(var(--base-font-size) * 1.6);
        }

        /* Utility classes */
        .mt-2 { margin-top: 0.5rem; }
        .mt-4 { margin-top: 1rem; }
        .mt-8 { margin-top: 2rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .my-4 { margin-top: 1rem; margin-bottom: 1rem; }
    </style>

    <!-- Additional styles pushed from child views -->
    @stack('styles')

    <!-- JavaScript -->
    @if(Route::currentRouteName() === 'upload.index')
        <script src="{{ asset('js/upload.js') }}" defer></script>
    @endif
</head>

<body class="flambient">
    <!-- Header/Nav -->
    <header class="flambient-header">
        <a href="{{ route('home') }}" class="flambient-title">
            <span class="flambient-prompt">> </span>FLAMBIENT.IO
        </a>
        <nav class="flambient-nav">
            <a href="{{ route('home') }}">~/home</a>
            <a href="{{ route('upload.index') }}">~/upload</a>
            <a href="{{ route('gallery.index') }}">~/gallery</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flambient-main">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="flambient-footer">
        <div class="terminal-text">
            <span class="flambient-prompt">$</span>
            echo "&copy; {{ date('Y') }} Flambient.io - Professional Real Estate Photography"
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
