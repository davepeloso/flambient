@extends('layouts.app')

@section('content')
<div class="flambient-terminal-card">
    <div class="flambient-terminal-content" id="home-content-main">
        <h1 class="flambient-title-large">Welcome to Flambient.io</h1>
        <p class="flambient-text mt-4">
            Professional real estate photography automation using the flambient method.
            Combine ambient and flash exposures with precision.
        </p>
        
        <div class="flambient-terminal-section mt-8" id="home-section-workflow">
            <h2 class="flambient-subtitle">How It Works</h2>
            <ol class="flambient-list">
                <li>Upload your exposure stacks</li>
                <li>First image: ambient exposure</li>
                <li>Subsequent images: flash exposures</li>
                <li>Get perfectly balanced interior photos</li>
            </ol>
        </div>

        <div class="flambient-terminal-section mt-8" id="home-section-pricing">
            <h2 class="flambient-subtitle">Pricing</h2>
            <ul class="flambient-list">
                <li>$0.50 per image</li>
                <li>$0.30 per image for bulk processing</li>
                <li>72-hour storage for processed images</li>
            </ul>
        </div>

        <div class="flambient-terminal-section mt-8">
            <a href="{{ route('upload.index') }}" class="flambient-button" id="test-home-button-upload">
                Start Processing →
            </a>
        </div>
    </div>
</div>
@endsection
