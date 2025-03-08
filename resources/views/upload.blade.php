@extends('layouts.app')

@section('content')
<div class="flambient-terminal">
    <div class="flambient-terminal-header">
        <div class="flambient-terminal-title">
            <span class="flambient-prompt">> </span>
            <span class="flambient-command">upload_images.sh</span>
        </div>
        <div class="flambient-terminal-controls">
            <span class="flambient-control">[-]</span>
            <span class="flambient-control">[□]</span>
            <span class="flambient-control">[x]</span>
        </div>
    </div>

    <div class="flambient-terminal-body">
        <!-- Upload Zone -->
        <div class="flambient-terminal-card">
            <div id="upload-zone-main"
                 class="flambient-upload-zone"
                 data-max-batch-size="1073741824"
                 data-max-file-size="8388608">
                <div class="flambient-upload-instructions">
                    <span class="flambient-prompt">> </span>
                    <span class="flambient-command">drop_images</span>
                    <span class="flambient-cursor">█</span>
                    <p class="flambient-text">Drag and drop images here</p>
                    <p class="flambient-text--small">Maximum file size: 8MB</p>
                    <div class="flambient-terminal-line">
                        <span class="flambient-prompt">$</span>
                        <span class="flambient-path">~/uploads</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Display -->
        <div id="upload-progress-container" class="flambient-terminal-section" style="display: none;">
            <div class="flambient-terminal-output">
                <div class="flambient-terminal-line">
                    <span class="flambient-prompt">> </span>
                    <span class="flambient-command">awaiting_files...</span>
                </div>
            </div>
        </div>

        <!-- Batch Information -->
        <div id="batch-info-display" class="flambient-terminal-section" style="display: none;">
            <div class="flambient-terminal-output">
                <div class="flambient-terminal-line">
                    <span class="flambient-prompt">> </span>
                    <span class="flambient-command">batch_info</span>
                </div>
                <div class="flambient-info-grid">
                    <div class="flambient-info-row">
                        <span class="flambient-label">Batch ID:</span>
                        <span class="flambient-value"></span>
                    </div>
                    <div class="flambient-info-row">
                        <span class="flambient-label">Status:</span>
                        <span class="flambient-value flambient-status--pending">Pending</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Display -->
        <div id="error-display" class="flambient-terminal-section" style="display: none;">
            <div class="flambient-terminal-output">
                <div class="flambient-terminal-line flambient-line--error">
                    <span class="flambient-prompt">> </span>
                    <span class="flambient-error-code">ERROR</span>
                    <span class="flambient-error-message"></span>
                </div>
                <div class="flambient-terminal-line">
                    <span class="flambient-prompt">$</span>
                    <span class="flambient-hint"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
:root {
    --color-bg-dark: #1a1a1a;
    --color-text-light: #e0e0e0;
    --color-accent: #4a9eff;
    --font-mono: 'JetBrains Mono', 'Fira Code', monospace;
}

.flambient-terminal {
    background-color: var(--color-bg-dark);
    color: var(--color-text-light);
    border-radius: 6px;
    overflow: hidden;
    margin: 2rem auto;
    max-width: 800px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.flambient-terminal-header {
    background-color: #2a2a2a;
    border-bottom: 1px solid var(--color-text-light);
    padding: 0.5rem 1rem;
    display: flex;
    justify-content: space-between;
}

.flambient-terminal-body {
    padding: 1rem;
}

.flambient-prompt {
    color: var(--color-accent);
    font-weight: bold;
    font-family: var(--font-mono), system-ui;
}

.flambient-command {
    color: var(--color-text-light);
    font-family: var(--font-mono), system-ui;
}

.flambient-cursor {
    animation: blink 1s step-end infinite;
}

.flambient-terminal-line {
    font-family: var(--font-mono), system-ui;
    margin: 0.25rem 0;
    line-height: 1.5;
}

.flambient-upload-zone {
    border: 2px dashed var(--color-accent);
    border-radius: 4px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flambient-upload-zone--active {
    border-color: var(--color-text-light);
    background-color: rgba(74, 158, 255, 0.05);
}

.flambient-upload-zone:hover {
    border-color: var(--color-text-light);
    background-color: rgba(74, 158, 255, 0.05);
}

.flambient-terminal-section {
    margin-top: 1rem;
    padding: 1rem;
    background-color: rgba(255, 255, 255, 0.05);
    border-radius: 4px;
}

.flambient-terminal-output {
    max-height: 300px;
    overflow-y: auto;
    padding: 0.5rem;
}

.flambient-text {
    color: var(--color-text-light);
    margin: 0.5rem 0;
}

.flambient-text--small {
    font-size: 0.875rem;
    color: #808080;
}

.flambient-text--success {
    color: #4aff4a;
}

.flambient-text--error {
    color: #ff4a4a;
}

.flambient-text--warning {
    color: #ffbb4a;
}

.flambient-error-code {
    color: #ff4a4a;
    font-weight: bold;
    margin-right: 0.5rem;
}

.flambient-hint {
    color: #808080;
    font-style: italic;
}

.flambient-status--success {
    color: #4aff4a;
}

.flambient-status--pending {
    color: #ffbb4a;
}

.flambient-info-grid {
    display: grid;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.flambient-info-row {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 1rem;
    align-items: center;
}

.flambient-label {
    color: var(--color-accent);
    font-family: var(--font-mono), system-ui;
}

.flambient-value {
    font-family: var(--font-mono), system-ui;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
</style>
@endpush

@push('scripts')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ asset('js/upload.js') }}"></script>
@endpush
