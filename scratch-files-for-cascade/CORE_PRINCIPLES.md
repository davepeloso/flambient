# FLAMBIENT.IO Core Principles

## 1. Delineation Field Principles

### Field Value Transitions
- Stack boundaries are determined solely by transitions in delineation field values
- No assumptions about flash status or lighting conditions
- Each field has a specific delineation value:
  - `flash`: 'Off, Did not fire'
  - `exposure_mode`: 'Auto'
  - `white_balance`: 'Auto'
  - `iso`: 400

### Stack Formation Rules
1. Every valid stack MUST start with a delineation value image
2. All subsequent images until the next delineation value belong to that stack
3. Stack boundaries ONLY occur at new delineation values
4. Original image sequence MUST be preserved
5. No artificial constraints on stack size

## 2. Application Views

### 1. Upload View (/)
- Terminal-like interface
- Drag & drop upload zone
- File size limit: 8MB per image
- Batch ID generated for tracking
- Progress indicators for upload status

### 2. Batch View (/batch/{id})
- Display image metadata in terminal-style table
- Delineation field selector
- Clear indication of delineation status for each image
- Stack preview before processing
- Processing script selector

### 3. Gallery View (/batch/{id}/gallery)
- Grid layout of processed images
- Download options:
  - Individual images ($0.50 each)
  - Complete batch ($0.30 per image)
- Processing status indicators
- Expiration countdown (72 hours)

### 4. Download View (/batch/{id}/download)
- Download link for processed images
- Payment processing
- Expiration timer
- Batch status

## 3. Technical Stack

### Backend
- Laravel 12.x
- PHP 8.4
- SQLite for development
- ImageMagick for processing

### Frontend
- Minimal JavaScript
- Terminal-like UI
- No frontend framework
- CSS Grid for gallery

### Processing
- ImageMagick scripts stored as templates
- Async processing queue
- Progress tracking
- Error handling

## 4. Data Models

### Image
- batch_id (UUID)
- sequence_column (int)
- Delineation fields:
  - flash (string)
  - exposure_mode (string)
  - white_balance (string)
  - iso (int)
- stack_id (foreign key)

### Stack
- batch_id (UUID)
- delineation_field (string)
- delineation_image_id (foreign key)

### Batch
- id (UUID)
- expires_at (timestamp)
- processing_script (string)
- status (enum)

## 5. Services

### ImageGroupingService
- Handles stack creation based on delineation field transitions
- Preserves image sequence
- Validates stack formation rules

### ProcessingService
- Manages ImageMagick script execution
- Handles async processing
- Tracks progress
- Error recovery

### StorageService
- Manages temporary storage
- Handles cleanup after expiration
- Secure download links

## 6. Security & Performance

### Security
- CSRF protection
- File type validation
- Secure download links
- Payment processing security

### Performance
- Async processing
- Batch operations
- Efficient file handling
- Automatic cleanup

## 7. User Experience

### Terminal-like Interface
- Command-line style navigation
- Clear status indicators
- Minimal UI elements

### Error Handling
- Clear error messages
- Recovery options
- Progress preservation

### Processing Feedback
- Real-time status updates
- Clear progress indicators
- Error notifications
