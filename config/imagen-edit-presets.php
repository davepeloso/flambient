<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Imagen AI Edit Parameter Presets
    |--------------------------------------------------------------------------
    |
    | Pre-configured edit parameters for different photography workflows.
    | Each preset defines the editing options sent to Imagen AI.
    |
    | Available parameters:
    |   - crop: Auto-crop images
    |   - portrait_crop: Portrait-specific cropping
    |   - headshot_crop: Headshot cropping
    |   - crop_aspect_ratio: Aspect ratio for cropping (e.g., "16:9", "4:3")
    |   - hdr_merge: Merge HDR brackets
    |   - straighten: Auto-straighten images
    |   - subject_mask: Create subject masks
    |   - photography_type: REAL_ESTATE, WEDDING, PORTRAIT, etc.
    |   - callback_url: Webhook URL for completion notifications
    |   - smooth_skin: Smooth skin for portraits
    |   - perspective_correction: Correct perspective distortion
    |   - window_pull: Recover detail in windows/bright areas
    |   - sky_replacement: Replace sky
    |   - sky_replacement_template_id: Sky template ID
    |   - hdr_output_compression: LOSSY or LOSSLESS
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    */
    'default' => env('IMAGEN_EDIT_PRESET', 'flambient_real_estate'),

    /*
    |--------------------------------------------------------------------------
    | Available Presets
    |--------------------------------------------------------------------------
    */
    'presets' => [

        // ═══════════════════════════════════════════════════════════════════
        // REAL ESTATE PRESETS
        // ═══════════════════════════════════════════════════════════════════

        'flambient_real_estate' => [
            'name' => 'Flambient Real Estate (Window Pull)',
            'description' => 'Optimized for flambient blended images with window detail recovery',
            'photography_type' => 'REAL_ESTATE',
            'window_pull' => true,
            'perspective_correction' => false,
            'straighten' => false,
            'crop' => false,
            'hdr_merge' => false,
            'sky_replacement' => false,
            'hdr_output_compression' => 'LOSSY',
        ],

        'real_estate_standard' => [
            'name' => 'Real Estate - Standard',
            'description' => 'Basic real estate editing with minimal adjustments',
            'photography_type' => 'REAL_ESTATE',
            'window_pull' => false,
            'perspective_correction' => false,
            'straighten' => false,
            'crop' => false,
            'hdr_merge' => false,
            'sky_replacement' => false,
            'hdr_output_compression' => 'LOSSY',
        ],

        'real_estate_hdr' => [
            'name' => 'Real Estate - HDR Merge',
            'description' => 'Merge HDR brackets for maximum dynamic range',
            'photography_type' => 'REAL_ESTATE',
            'window_pull' => true,
            'perspective_correction' => true,
            'straighten' => true,
            'crop' => false,
            'hdr_merge' => true,
            'sky_replacement' => false,
            'hdr_output_compression' => 'LOSSLESS',
        ],

        'real_estate_sky_replacement' => [
            'name' => 'Real Estate - Sky Replacement',
            'description' => 'Real estate with automatic sky replacement',
            'photography_type' => 'REAL_ESTATE',
            'window_pull' => true,
            'perspective_correction' => true,
            'straighten' => true,
            'crop' => false,
            'hdr_merge' => false,
            'sky_replacement' => true,
            'sky_replacement_template_id' => null, // null = auto-select
            'hdr_output_compression' => 'LOSSY',
        ],

        'real_estate_full_correction' => [
            'name' => 'Real Estate - Full Correction',
            'description' => 'Complete real estate workflow with all corrections',
            'photography_type' => 'REAL_ESTATE',
            'window_pull' => true,
            'perspective_correction' => true,
            'straighten' => true,
            'crop' => true,
            'crop_aspect_ratio' => 'false', // false = no specific ratio
            'hdr_merge' => false,
            'sky_replacement' => false,
            'hdr_output_compression' => 'LOSSY',
        ],

        // ═══════════════════════════════════════════════════════════════════
        // PORTRAIT & WEDDING PRESETS
        // ═══════════════════════════════════════════════════════════════════

        'portrait_standard' => [
            'name' => 'Portrait - Standard',
            'description' => 'Standard portrait editing with skin smoothing',
            'photography_type' => 'PORTRAIT',
            'smooth_skin' => true,
            'portrait_crop' => false,
            'crop' => false,
            'hdr_merge' => false,
            'subject_mask' => false,
            'hdr_output_compression' => 'LOSSY',
        ],

        'portrait_headshot' => [
            'name' => 'Portrait - Headshot',
            'description' => 'Headshot-specific cropping and skin smoothing',
            'photography_type' => 'PORTRAIT',
            'smooth_skin' => true,
            'headshot_crop' => true,
            'crop' => false,
            'hdr_merge' => false,
            'subject_mask' => true,
            'hdr_output_compression' => 'LOSSY',
        ],

        'wedding_standard' => [
            'name' => 'Wedding - Standard',
            'description' => 'Wedding photography with natural skin tones',
            'photography_type' => 'WEDDING',
            'smooth_skin' => true,
            'crop' => false,
            'hdr_merge' => false,
            'subject_mask' => false,
            'hdr_output_compression' => 'LOSSY',
        ],

        'wedding_hdr' => [
            'name' => 'Wedding - HDR',
            'description' => 'Wedding with HDR bracket merging',
            'photography_type' => 'WEDDING',
            'smooth_skin' => true,
            'crop' => false,
            'hdr_merge' => true,
            'subject_mask' => false,
            'hdr_output_compression' => 'LOSSLESS',
        ],

        // ═══════════════════════════════════════════════════════════════════
        // CUSTOM / ADVANCED
        // ═══════════════════════════════════════════════════════════════════

        'minimal' => [
            'name' => 'Minimal - AI Only',
            'description' => 'Only apply AI profile styling, no corrections',
            'photography_type' => 'REAL_ESTATE',
            'window_pull' => false,
            'perspective_correction' => false,
            'straighten' => false,
            'crop' => false,
            'hdr_merge' => false,
            'sky_replacement' => false,
            'smooth_skin' => false,
            'hdr_output_compression' => 'LOSSY',
        ],

        'maximum_quality' => [
            'name' => 'Maximum Quality - Lossless',
            'description' => 'All corrections with lossless compression',
            'photography_type' => 'REAL_ESTATE',
            'window_pull' => true,
            'perspective_correction' => true,
            'straighten' => true,
            'crop' => true,
            'crop_aspect_ratio' => 'false',
            'hdr_merge' => false,
            'sky_replacement' => false,
            'hdr_output_compression' => 'LOSSLESS',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Preset Categories
    |--------------------------------------------------------------------------
    |
    | Organize presets by category for easier browsing
    |
    */
    'categories' => [
        'real_estate' => [
            'label' => '🏠 Real Estate',
            'presets' => [
                'flambient_real_estate',
                'real_estate_standard',
                'real_estate_hdr',
                'real_estate_sky_replacement',
                'real_estate_full_correction',
            ],
        ],
        'portrait' => [
            'label' => '👤 Portrait & Headshot',
            'presets' => [
                'portrait_standard',
                'portrait_headshot',
            ],
        ],
        'wedding' => [
            'label' => '💒 Wedding',
            'presets' => [
                'wedding_standard',
                'wedding_hdr',
            ],
        ],
        'advanced' => [
            'label' => '⚙️  Advanced / Custom',
            'presets' => [
                'minimal',
                'maximum_quality',
            ],
        ],
    ],
];
