# Upload Page Documentation

## Core Purpose
The Upload Page is dedicated to file upload and metadata extraction, following the principle of page independence from our core architectural principles.

## Primary Responsibilities

### 1. File Upload Management
- Handles multiple JPEG image uploads
- Validates file types and sizes
- Creates and manages batch IDs
- Provides real-time upload progress feedback

### 2. EXIF Data Processing
- Extracts essential EXIF metadata
- Identifies flash vs. ambient exposures
- Validates exposure settings
- Stores metadata in dedicated tables

## Technical Implementation

### Database Tables
Following project-wide naming conventions:
```sql
flambient_images
- id (UUID)
- batch_id (UUID)
- filename (string)
- file_size (integer)
- storage_path (string)
- mime_type (string)
- timestamps

flambient_image_metadata
- image_id (UUID)
- flash_status (string)
- exposure_mode (string)
- white_balance (string)
- iso (integer)
- exposure_time (string)
- aperture (string)
- tag (string, nullable)
- ignore (boolean)
- timestamps
```

### Services
```php
ExifExtractionService
- Focused on EXIF data extraction
- Returns structured response format
- Handles errors with clear messages
- Logs extraction process
```

### Frontend Components
Following BEM naming conventions:
```css
.flambient-upload-zone
.flambient-terminal-output
.flambient-error-display
.flambient-batch-info
```

## Page Independence
- Operates independently of other pages
- Communicates via batch IDs only
- Maintains its own state
- No dependencies on previous/next pages

## Error Handling
Following service definition guidelines:
1. Clear error messages in logs
2. Structured error responses
3. Transaction management
4. User-friendly error display

## Success Criteria
1. Successful file upload
2. Accurate EXIF extraction
3. Proper metadata storage
4. Clear user feedback
5. Batch ID generation
6. Error handling and recovery

## Development Guidelines
1. Keep focused on upload and metadata
2. Maintain page independence
3. Follow naming conventions
4. Use proper error handling
5. Provide clear user feedback
