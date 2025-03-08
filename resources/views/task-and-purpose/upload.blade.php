{{--
View: upload.blade.php
Location: resources/views/task-and-purpose/upload.blade.php
Purpose: Independent upload interface for Flambient.io
--}}

@extends('task-and-purpose.layouts.documentation')

@section('title', 'Upload View Documentation')

@section('documentation')
{{-- 
View: upload.blade.php
Type: Upload Interface
Route: /upload
--}}

# Upload View Documentation

## Purpose
The upload view is a self-contained interface for image upload and metadata extraction:
- Handles batch uploads of images
- Validates file sizes and formats
- Extracts and stores EXIF metadata
- Generates unique batch ID
- Operates independently of other pages

## Core Requirements

### File Validation
1. Size Constraints
   - Total batch size: < 1GB
   - Individual images: < 8MB
   - Format: JPEG only

2. Batch Management
   - Generate unique batch ID upon successful upload
   - Store in `flambient_batches` table
   - Track upload progress
   - Provide immediate feedback

3. EXIF Requirements
   - Extract metadata synchronously
   - Required fields must contain at least one MetaDelineation value:
     - Flash status
     - Exposure mode
     - White balance
     - ISO value
   - Store extracted metadata in database

## Page Independence

### Input Requirements
- No initial session state required
- Clean entry point for new uploads
- Generates its own batch ID
- No dependencies on previous pages

### Output Contract
- Provides batch ID for subsequent pages
- Stores complete metadata in database
- No assumptions about next page's existence

## Implementation Details

### Required JavaScript
```javascript
// Minimal JavaScript for drag-and-drop functionality
const uploadZone = {
    // Drag & drop event handlers
    onDragEnter: (e) => { /* highlight drop zone */ },
    onDragLeave: (e) => { /* remove highlight */ },
    onDrop: async (e) => { /* handle file upload */ },
    
    // Progress tracking
    updateProgress: (progress) => { /* update terminal output */ },
    
    // Validation feedback
    showValidationError: (error) => { /* display in terminal style */ }
};
```

### Controller Logic (To Be Implemented)
```php
class UploadController {
    private ExifExtractionService $exifService;
    private StorageService $storageService;

    // Handle file upload
    // Validate file size and format
    // Extract and validate EXIF data
    // Generate batch ID
    // Store files and metadata
    // Return batch status and ID
}
```

### Database Schema (To Be Implemented)
```sql
-- Following naming convention: flambient_*
CREATE TABLE flambient_batches (
    id CHAR(36) PRIMARY KEY,
    total_size BIGINT,
    image_count INT,
    created_at TIMESTAMP,
    status VARCHAR(50),
    -- Additional fields to be determined
);

CREATE TABLE flambient_images (
    id CHAR(36) PRIMARY KEY,
    batch_id CHAR(36),
    user_id CHAR(36),
    filename VARCHAR(255),
    file_size BIGINT,
    storage_path VARCHAR(255),
    mime_type VARCHAR(50),
    created_at TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES flambient_batches(id)
);

CREATE TABLE flambient_image_metadata (
    image_id CHAR(36) PRIMARY KEY,
    flash_status VARCHAR(50),
    exposure_mode VARCHAR(50),
    white_balance VARCHAR(50),
    iso INTEGER,
    created_at TIMESTAMP,
    FOREIGN KEY (image_id) REFERENCES flambient_images(id)
);
```

### Frontend Components
1. Terminal Header
   ```html
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
   ```

2. Upload Zone (with drag & drop)
(dave's notes, id like the drag and drop to be very large 95% of the terminal width)
   ```html
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
   ```

3. Progress Display
   ```html
   <div id="upload-progress-container" class="flambient-terminal-section">
       <div class="flambient-terminal-output">
           <div class="flambient-terminal-line">
               <span class="flambient-prompt">> </span>
               <span class="flambient-command">uploading_files</span>
           </div>
           <div class="flambient-progress-bar">
               <div class="flambient-progress-fill"></div>
               <span class="flambient-progress-text">
                   [===>----] 42%
               </span>
           </div>
           <div class="flambient-terminal-line">
               <span class="flambient-timestamp">[16:16:18]</span>
               <span class="flambient-status">Processing image_001.jpg...</span>
           </div>
       </div>
   </div>
   ```

4. Batch Information
   ```html
   <div id="batch-info-display" class="flambient-terminal-section">
       <div class="flambient-terminal-output">
           <div class="flambient-terminal-line">
               <span class="flambient-prompt">> </span>
               <span class="flambient-command">batch_info</span>
           </div>
           <div class="flambient-info-grid">
               <div class="flambient-info-row">
                   <span class="flambient-label">Batch ID:</span>
                   <span class="flambient-value">b4f2c8e9</span>
               </div>
               <div class="flambient-info-row">
                   <span class="flambient-label">Status:</span>
                   <span class="flambient-value flambient-status--success">✓ Complete</span>
               </div>
           </div>
       </div>
   </div>
   ```

5. Error Display
   ```html
   <div id="error-display" class="flambient-terminal-section">
       <div class="flambient-terminal-output">
           <div class="flambient-terminal-line flambient-line--error">
               <span class="flambient-prompt">> </span>
               <span class="flambient-error-code">ERROR</span>
               <span class="flambient-error-message">
                   File exceeds maximum size: image_large.jpg (10MB)
               </span>
           </div>
           <div class="flambient-terminal-line">
               <span class="flambient-prompt">$</span>
               <span class="flambient-hint">Try reducing file size to < 8MB</span>
           </div>
       </div>
   </div>
   ```

### Additional CSS Classes
```css
.flambient-terminal-header {
    background-color: var(--color-bg-dark);
    border-bottom: 1px solid var(--color-text-light);
    padding: 0.5rem 1rem;
    display: flex;
    justify-content: space-between;
}

.flambient-prompt {
    color: var(--color-accent);
    font-weight: bold;
}

.flambient-command {
    color: var(--color-text-light);
}

.flambient-cursor {
    animation: blink 1s step-end infinite;
}

.flambient-terminal-line {
    font-family: var(--font-mono);
    margin: 0.25rem 0;
}

.flambient-timestamp {
    color: var(--color-accent);
    margin-right: 0.5rem;
}

.flambient-progress-bar {
    height: 1.5rem;
    background: rgba(74, 158, 255, 0.1);
    border: 1px solid var(--color-accent);
}

.flambient-progress-fill {
    background: var(--color-accent);
    height: 100%;
    transition: width 0.3s ease;
}

.flambient-error-code {
    color: #ff4a4a;
    font-weight: bold;
}

.flambient-hint {
    color: #808080;
    font-style: italic;
}

@keyframes blink {
    50% { opacity: 0; }
}
```

### User Flow
1. Initial State
   - Display clean upload interface
   - Show size constraints
   - Explain file requirements

2. During Upload
   - Show terminal-style progress
   - Display individual file validations
   - Report EXIF extraction status

3. Completion
   - Display batch ID (primary output)
   - Show summary of uploaded files
   - Show metadata extraction summary
   - Provide link to next page (optional)

### Error States
1. File Validation Errors
   - Size limit exceeded
   - Invalid format
   - Corrupt file

2. Metadata Errors
   - Missing EXIF data
   - Invalid MetaDelineation values
   - Extraction failure

3. Storage Errors
   - Disk space issues
   - Permission problems
   - Network failures

## Dependencies
- `layouts/app.blade.php` (Base layout)
- `App\Services\ExifExtractionService` (Metadata extraction and validation)
- `App\Services\StorageService` (Temporary storage)
- `App\Constants\NamingConventions` (Style conventions)
- Terminal-inspired styles from welcome view
- Minimal JavaScript for drag & drop functionality
- EXIF extraction is synchronous for immediate validation


## Notes
- Follows terminal-inspired design system
- Uses BEM methodology with flambient- prefix
- Element IDs follow {category}-{type}-{purpose} convention
- Synchronous EXIF extraction for immediate validation
- Maintains page independence
- Focused solely on upload and metadata
- Clear entry and exit points
- Requires minimal JavaScript for drag & drop

## Research for Cascade written on 3-7-25 by Dave Peloso
- EIFXTOOL by Phil Harvey https://exiftool.org/#boldly
- https://photostructure.github.io/exiftool-vendored.js/#exiftool-vendored
- 
## Possible Exif tags / Interface EXIFTags
interface EXIFTags {
    <!-- Acceleration?: number;
    AntiAliasStrength?: number; -->
    ApertureValue?: number;
    <!-- Artist?: string;
    AsShotNeutral?: string;
    Basel   ineExposure?: number;
    BlackLevel?: string;
    BlackLevelBlue?: number;
    BlackLevelGreen?: number;
    BlackLevelRed?: number;
    BlackLevelRepeatDim?: string; -->
    BrightnessValue?: number;
    <!-- CameraElevationAngle?: number;
    CFAPlaneColor?: string;
    CFARepeatPatternDim?: string;
    ChromaticAberrationCorrection?: string; -->
    ColorSpace?: string;
    <!-- CompositeImage?: string;
    CompressedBitsPerPixel?: number; -->
    <!-- Copyright?: string; -->
    CreateDate?: string | ExifDateTime;
    <!-- CustomRendered?: string;
    DateTimeOriginal?: string | ExifDateTime;
    DefaultCropOrigin?: string;
    DefaultCropSize?: string;
    DeviceSettingDescription?: string | BinaryField;
    DNGBackwardVersion?: string;
    DNGVersion?: string;
    DocumentName?: string;
    ExifImageHeight?: number;
    ExifImageWidth?: number; -->
    <!-- ExposureIndex?: number; -->
    ExposureProgram?: string;
    ExposureTime?: string;
    <!-- FileSource?: string; -->
    Flash?: string;
    <!-- FlashEnergy?: number; -->
    FNumber?: number;
    FocalLength?: string;
    <!-- FocalLengthIn35mmFormat?: string;
    FocalPlaneResolutionUnit?: string;
    FocalPlaneXResolution?: number;
    FocalPlaneYResolution?: number;
    GainControl?: string;
    Gamma?: number;
    HighISOMultiplierBlue?: number;
    HighISOMultiplierGreen?: number;
    HighISOMultiplierRed?: number;
    HostComputer?: string; -->
    <!-- ImageDescription?: string; -->
    <!-- ImageTitle?: number;
    InteropIndex?: string;
    InteropVersion?: string; -->
    ISO?: number;
    ISOSpeed?: number;
    <!-- JpgFromRaw?: BinaryField;
    JpgFromRawLength?: number;
    JpgFromRawStart?: number;
    LensInfo?: string;
    LensMake?: string;
    LensModel?: string;
    LensSerialNumber?: string;
    LightSource?: string;
    LinearityLimitBlue?: number;
    LinearityLimitGreen?: number;
    LinearityLimitRed?: number;
    Make?: string;
    MakerNoteSamsung1a?: string | BinaryField;
    MakerNoteUnknownBinary?: string | BinaryField;
    MakerNoteUnknownText?: string;
    MaxApertureValue?: number; -->
    MeteringMode?: string;
    <!-- Model?: string;
    Model2?: string;
    ModifyDate?: string | ExifDateTime;
    Noise?: number;
    NoiseProfile?: string;
    NoiseReductionParams?: string;
    Padding?: string | BinaryField;
    PageName?: string;
    PanasonicRawVersion?: string; -->
    <!-- Photographer?: number; -->
    <!-- PhotometricInterpretation?: string;
    PlanarConfiguration?: string;
    Pressure?: number;
    PreviewDateTime?: string | ExifDateTime;
    PreviewTIFF?: string | BinaryField;
    PrimaryChromaticities?: string;
    ProcessingSoftware?: string;
    RawDataOffset?: number;
    RawDataUniqueID?: string;
    RawFormat?: number;
    RawImageSegmentation?: string;
    RecommendedExposureIndex?: number;
    ReferenceBlackWhite?: string;
    RelatedImageFileFormat?: string;
    RelatedImageHeight?: number;
    RelatedImageWidth?: number;
    RelatedSoundFile?: string;
    ResolutionUnit?: string;
    RowsPerStrip?: number;
    SamplesPerPixel?: number; -->
    <!-- SceneCaptureType?: string; -->
    <!-- SceneType?: string;
    SensingMethod?: string;
    SensitivityType?: string; -->
    ShutterSpeedValue?: string;
    <!--
     Software?: string;
    SonyRawFileType?: string;
    SonyToneCurve?: string;
    SpatialFrequencyResponse?: number;
    SRawType?: number;
    StandardOutputSensitivity?: number;
    StripByteCounts?: number;
    StripOffsets?: number;
    SubfileType?: string;
    SubjectArea?: string;
    SubjectDistance?: string;
    SubjectDistanceRange?: string;
    SubjectLocation?: number;
    SubSecTime?: number;
    SubSecTimeDigitized?: number;
    SubSecTimeOriginal?: number;
    ThumbnailImage?: BinaryField;
    ThumbnailLength?: number;
    ThumbnailOffset?: number;
    ThumbnailTIFF?: BinaryField;
    TileByteCounts?: string | BinaryField;
    TileLength?: number;
    TileOffsets?: string | BinaryField;
    TileWidth?: number;
    TimeZoneOffset?: string | number;
    TransferFunction?: string | BinaryField;
    UniqueCameraModel?: string;
    UserComment?: string;
    WaterDepth?: number;
    WhiteLevel?: number;
    WhitePoint?: string;
    XResolution?: number;
    YCbCrCoefficients?: string;
    YCbCrPositioning?: string;
    YResolution?: number; -->
}
@endsection
