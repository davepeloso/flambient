<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flambient.io Documentation - @yield('title', 'View Documentation')</title>
    <style>
        :root {
            --bg-color: #1a1a1a;
            --text-color: #f0f0f0;
            --accent-color: #4a9eff;
            --border-color: #333;
            --code-bg: #2a2a2a;
        }

        body {
            font-family: 'Courier New', monospace;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.6;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1, h2, h3 {
            color: var(--accent-color);
            margin-top: 2rem;
            margin-bottom: 1rem;
        }

        h1 { font-size: 2rem; }
        h2 { font-size: 1.5rem; }
        h3 { font-size: 1.25rem; }

        p {
            margin: 1rem 0;
        }

        ul, ol {
            margin: 1rem 0;
            padding-left: 2rem;
        }

        li {
            margin: 0.5rem 0;
        }

        code {
            background-color: var(--code-bg);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }

        pre {
            background-color: var(--code-bg);
            padding: 1rem;
            border-radius: 4px;
            overflow-x: auto;
            margin: 1rem 0;
        }

        pre code {
            padding: 0;
            background-color: transparent;
        }

        .doc-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .doc-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .doc-section {
            margin: 2rem 0;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .command-block {
            background-color: var(--code-bg);
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid var(--accent-color);
        }

        .note {
            border-left: 4px solid var(--accent-color);
            padding-left: 1rem;
            margin: 1rem 0;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="doc-header">
        <h1>Flambient.io View Documentation</h1>
        <div class="doc-meta">
            Generated: {{ date('Y-m-d H:i:s') }}
        </div>
    </div>

    <div class="doc-content">
        @yield('documentation')
    </div>
</body>
</html>
