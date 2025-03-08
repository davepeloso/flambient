## Description ##
 - Flambient.io is a website app built for professional real estate photographers that saves users time and money by automating the post-processing of interior photography using the "flash / ambient" or "flambient" method of merging, blending and masking the multiple exposures of a flambient image. The flambient photography technique combines the natural light of an ambient shot with the color accuracy and detail of a flash shot to create a balanced, natural-looking image. This method is widely used in real estate photography to showcase interiors effectively.

 - Because we are working with, and creating images using a complex batch processing script we differentiate where the image is in the process is by reffering to images in the followong manner:

  - Images outside of a ExposureStack are called **"images"**
  - Images within a ExposureStack are called as **"exposures"**
  - Successfully processed images are referred to as **"flambients"**pictures.
 - Understanding flash/ambient style interior photography:

  - The first image is captured without a flash, this is referred to as "Ambient"

  - The next images are captured with a flash, these pictures are referred to as "Flashes"

## Process Definitions ##

1. **''Flambient''**
    2. A new picture created from the **''ExposureStack''** 
 
2. **''ExposureStack''**
    3. The grouped sequence of images used to create a ''flambient'' picture, these images are identical in composition because the camera has not been moved and only the camera settings have been changed, specifically the shutter speed, ISO, aperture, and flash.

2. **''MetaDelineation''**
    3. A variabe for one of the following EIFX values used to group the **''ExposureStack''** and identify **''ExposureGroup''** during processing.

     - - The following EXIF values can be used as **''MetaDelineation''**
        1. 'ISOSpeedRatings'
        2. 'ShutterSpeedValue'
        3. 'ApertureValue'
        4. 'FNumber'
        5. 'ExposureProgram'
        6. 'Flash'
        7. 'ExposureMode'
        8. 'WhiteBalance'

13. **''ExposureGroup''**
  14. The group or grouped sequence of images within the **''ExposureStack''**  - ExposureGroup types are as follows:
        15. [Ambient]
              1. Always the first image. Typically a show shutter speed no flash will be returned.
        16. [Flash]
              4. Always after the ambient image, typically a high shutter speed. There can be one FLASH or many.
 
 
  **How it works**
- 1. The first step is to extract EXIF data from the images and store the data into a database.

 2. Image EXIF Data is used to sort images into **''ExposureStack''** and within the **''ExposureStack''** are **''ExposureGroup''**.

 3. For example: If **''ExposureStack**[1]'' has [4] images, the first image has **''ExposureGroup''** = [AMBIENT], the other [4] have **''ExposureGroup''** = [FLASH]

 4. The [AMBIENT] ExposureGroup will have one variabe /path/to/DSC_001.jpg and the [FLASH] ExposureGroup will have one or more variables /path/to/DSC_002.jpg /path/to/DSC_003.jpg /path/to/DSC_004.jpg .The Variables are passed to the bash script, posssibly as environmental variables then expanded in the "cat" ImageMagick script. 

 **EXAMPLE OF THE FIRST SCRIPT FUNCTION: 
 ## THE FULL SCRIPT IS BELOW, AS CURRENT CODE TO GROUP IMAGES BASED ON 'ExposureProgram' EXIF value. LINE 179 - 220 
 <CODE> 

    # Function 1 to ensure export directory exists

        function setup_environment() {

            echo "Setting up environment..."
            mkdir -p export

}

    # Function 2 define the path to the images and the images to be used:

        function setup_variables() {
            echo "Setting up environment..."
            export SHEBANG='#!/bin/env magick-script'
            export IMG_PATH="public/storage/images/SESSION_ID"
            export OUTPUT_FILE="generateSequenceNumber.jpeg"
            export SCRIPT_PATH="public/storage/SESSION_ID/generateSequenceNumber.mgk"
}

    # Step 1: Exteact AMBIENT_IMAGE_MASK from the ambient image & save to memory / delete from stack

        function create_ambient_mask() {
        cat << EOF > script.mgk
        $SHEBANG
        $AMBIENT -channel B -separate -level 80%,100%,5.0 -write mpr:ambient_mask_alpha +delete
        EOF
}

</CODE>

  4. In order to identify the stack, we compare the current MetaDelineation and the previous MetaDelineation, a stack is created on the first MetaDelineation, the next image(s) with a different MetaDelineation are added to the stack untill the MetaDelineation equals the first MetaDelineation which triggers a new **''ExposureStack''**
  ''

 If $MetaDelineation = ['ExposureProgram']

  **Images**
  0. DSC_001.jpg -> $MetaDelineation = [1]
  0. DSC_002.jpg -> $MetaDelineation = [2]
  0. DSC_003.jpg -> $MetaDelineation = [2]
  0. DSC_004.jpg -> $MetaDelineation = [2]
  0. DSC_005.jpg -> $MetaDelineation = [1]
  0. DSC_006.jpg -> $MetaDelineation = [2]
  0. DSC_007.jpg -> $MetaDelineation = [2]
  0. DSC_008.jpg -> $MetaDelineation = [2]

  The grouped stacks look like this:

  **Images**
  - ExposureStack[1]
  0. DSC_001.jpg -> $ExposureGroup = [AMBIENT]
  0. DSC_002.jpg -> $ExposureGroup = [FLASH]
  0. DSC_003.jpg -> $ExposureGroup = [FLASH]
  0. DSC_004.jpg -> $ExposureGroup = [FLASH]
  - ExposureStack[2]
  0. DSC_005.jpg -> $ExposureGroup = [AMBIENT]
  0. DSC_006.jpg -> $ExposureGroup = [FLASH]
  0. DSC_007.jpg -> $ExposureGroup = [FLASH]
  0. DSC_008.jpg -> $ExposureGroup = [FLASH]


  The [AMBIENT] ExposureGroup and [FLASH] ExposureGroup variables are used to generate a ImageMagick script for ExposureStack[1] and stored as a ExposureStack[1].mgk file for further modification if nessary. The file is executed and and the flambient picture is stored in a retreable location.
  
-------------------------------------------------------------------
# User Experence

- The first view (index.blade.php) will consist of a  large drag & drop upload form, users will upload up to 100 images that will have a maximum file size of 8 MB per image.

- The second view (stack.blade.php) will be an almost full size data table of image EXIF data and names, here the user will select the **''MetaDelineation''** value via a drop down selector with the **''MetaDelineation''** values to sort the images into **''ExposureStack''** so the user can confirm the proper grouping of the images. A second drop down will be made visble after the **''MetaDelineation''** value is selected, this second DDL will be used to select the various ImageMagick post-processing scripts. Currently I have one full ImageMagick.mgk script named "Alpha", more will be created soon.


- The third page ({{$SelectedScript}}.blade.php) will present the completed ImageMagick post-processed flambient images at 150px thumbnails, in a grid with the options to download all or indivdual images for .50 for individual images or .30 per image for all images.

- A zip file of flambient images is compressed and stored for user download and a URL to the zip is displayed.

- All data and images are destroyed after 72 hours.

-------------------------------------------------------------------

# Workflow Example

- User Uploads Images
  - Images are stored in the database using the `Image` model.
  - **Data Storage:** The `Image` model stores all uploaded images and their metadata. The grouping logic operates on collections of `Image` instances retrieved from the database.

### 1.1 Fetch and Display Images

- Use Eloquent to fetch and display images.

## 2. User Selects Grouping Type
  - The user selects a g**''MetaDelineation''** (e.g., “Exposure Mode”) via a dropdown in the UI.
  - Pass the collection of `Image` models to the `ImageGroupingService` for dynamic grouping.

## 3. Dynamic Grouping
- The selected **''MetaDelineation''** is passed to the backend, where the `ImageGroupingService` dynamically applies the appropriate **''MetaDelineation''** logic.

### The Role of the `ImageGroupingService`
- The `ImageGroupingService` acts as a central manager that delegates the grouping task to the appropriate “GroupType” class based on user input.
- Each “GroupType” or **''MetaDelineation''** class represents a specific grouping logic and encapsulates the rules for grouping images based on a particular metadata type.

## 4. Display Grouped Images
- The grouped images are returned to the frontend for display.

## 5. Process Images
- Once the user finalizes their selection, the grouped images are sent to Symfony’s ImageMagick commands for processing.

## Why This Structure Works
- **Separation of Concerns:** Each “GroupType” class handles only one specific type of grouping logic, making it easier to maintain and extend.
- **Reusability:** The `ImageGroupingService` can be reused across different parts of your application.
- **Scalability:** Adding new grouping types is as simple as creating a new class and registering it in the service.
- **Flexibility:** Models remain focused on data storage/retrieval, while grouping logic is handled independently.



### Products
- Images displayed as products user can purchase individually or as a package. .50 pre image or .30xn for package.

## delivery
- Unique URI generated and displayed or emailed for delivery. Link expires after one week then all images and data are destryed.

## CURRENT CODE TO GROUP IMAGES BASED ON 'ExposureProgram' EXIF value. 
 - I want to refactor this to include all **''MetaDelineation''** values, chosen by user.

<CODE>

        public function groupImageExposures(Collection $imageExposures): array
        {
            $groupedExposures = [];
            $groupNumber = 1;
            $firstExposureProgram = null;
            $previousExposureProgram = null;

            foreach ($imageExposures->sortBy('sequence_column') as $imageExposure) {
                $currentExposureProgram = $imageExposure->exposure_program;

                if ($firstExposureProgram === null) {
                    $firstExposureProgram = $currentExposureProgram;
                } elseif ($currentExposureProgram === $firstExposureProgram && $currentExposureProgram !== $previousExposureProgram) {
                    $groupNumber++;
                }

                $groupName = "Flash / Ambient $groupNumber";
                if (!isset($groupedExposures[$groupName])) {
                    $groupedExposures[$groupName] = [
                        'mode_one'  => [],
                        'mode_two'  => [],
                        'exposures' => [],
                    ];
                }

                // Add the image name to the appropriate mode array
                if ($currentExposureProgram === $firstExposureProgram) {
                    $groupedExposures[$groupName]['mode_one'][] = $imageExposure->filename;
                } else {
                    $groupedExposures[$groupName]['mode_two'][] = $imageExposure->filename;
                }

                // Keep the original exposure object in the 'exposures' array
                $groupedExposures[$groupName]['exposures'][] = $imageExposure;

                $previousExposureProgram = $currentExposureProgram;
            }

            return $groupedExposures;
        }

</CODE>

Here is the current image grouping service in totality, I am totally open to re-factoring to better optimize the new version and functionality.

<CODE>
    <?php

    namespace App\Services;

    use App\Models\ImageExposure;
    use Illuminate\Support\Collection;
    use Rafiki23\MetadataExtractor\MetadataExtractor;

    class ImageExposureGroupingService
    {
        public function processAndSaveImageExposure($image)
        {
            $path = $image->store('images', 'public');
            $filename = $image->getClientOriginalName();
            $fullPath = storage_path('app/public/' . $path);

            $exifData = $this->extractExifData($fullPath);
            $relevantExif = $this->extractRelevantExif($exifData);

            return ImageExposure::create([
                'filename'            => $filename,
                'path'                => $path,
                'format'              => $image->getClientMimeType(),
                'iso'                 => $relevantExif['ISOSpeedRatings'] ?? null,
                'shutter_speed'       => $relevantExif['ShutterSpeedValue'] ?? null,
                'aperture'            => $relevantExif['ApertureValue'] ?? $relevantExif['FNumber'] ?? null,
                'exposure_program'    => $relevantExif['ExposureProgram'] ?? null,
                'flash'               => $relevantExif['Flash'] ?? null,
                'exposure_mode'       => $relevantExif['ExposureMode'] ?? null,
                'white_balance'       => $relevantExif['WhiteBalance'] ?? null,
                'additional_metadata' => $relevantExif,
                'sequence_column'     => $this->generateSequenceNumber($exifData),
            ]);
        }

        private function extractExifData($fullPath): array
        {
            $exifData = MetadataExtractor::extractEXIF($fullPath);

            if ($exifData === null) {
                return []; // Return an empty array if no EXIF data is found
            }

            return $exifData;
        }

        private function extractRelevantExif(array $exifData): array
        {
            $relevantFields = [
                'ISOSpeedRatings',
                'ShutterSpeedValue',
                'ApertureValue',
                'FNumber',
                'ExposureProgram',
                'Flash',
                'ExposureMode',
                'WhiteBalance'
            ];

            return array_intersect_key($exifData['EXIF'] ?? [], array_flip($relevantFields));
        }

        private function generateSequenceNumber(array $exifData): int
        {
            // Generate a sequence number based on the date and time
            // This is a simple example, you might want to adjust this based on your specific needs
            $dateTime = $exifData['EXIF']['DateTimeOriginal'] ?? '0000:00:00 00:00:00';
            return strtotime($dateTime);
        }

        public function groupImageExposures(Collection $imageExposures): array
        {
            $groupedExposures = [];
            $groupNumber = 1;
            $firstExposureProgram = null;
            $previousExposureProgram = null;

            foreach ($imageExposures->sortBy('sequence_column') as $imageExposure) {
                $currentExposureProgram = $imageExposure->exposure_program;

                if ($firstExposureProgram === null) {
                    $firstExposureProgram = $currentExposureProgram;
                } elseif ($currentExposureProgram === $firstExposureProgram && $currentExposureProgram !== $previousExposureProgram) {
                    $groupNumber++;
                }

                $groupName = "Flash / Ambient $groupNumber";
                if (!isset($groupedExposures[$groupName])) {
                    $groupedExposures[$groupName] = [
                        'mode_one'  => [],
                        'mode_two'  => [],
                        'exposures' => [],
                    ];
                }

                // Add the image name to the appropriate mode array
                if ($currentExposureProgram === $firstExposureProgram) {
                    $groupedExposures[$groupName]['mode_one'][] = $imageExposure->filename;
                } else {
                    $groupedExposures[$groupName]['mode_two'][] = $imageExposure->filename;
                }

                // Keep the original exposure object in the 'exposures' array
                $groupedExposures[$groupName]['exposures'][] = $imageExposure;

                $previousExposureProgram = $currentExposureProgram;
            }

            return $groupedExposures;
        }

        public function groupImageExposuresForBash(Collection $imageExposures): array
        {
            $groupedExposures = [];
            $groupNumber = 1;
            $firstExposureProgram = null;
            $previousExposureProgram = null;

            foreach ($imageExposures->sortBy('sequence_column') as $imageExposure) {
                $currentExposureProgram = $imageExposure->exposure_program;

                if ($firstExposureProgram === null) {
                    $firstExposureProgram = $currentExposureProgram;
                } elseif ($currentExposureProgram === $firstExposureProgram && $currentExposureProgram !== $previousExposureProgram) {
                    $groupNumber++;
                }

                $groupName = "Flash_Ambient_$groupNumber";
                if (!isset($groupedExposures[$groupName])) {
                    $groupedExposures[$groupName] = [
                        'mode_one' => [],
                        'mode_two' => [],
                    ];
                }

                // Add the full path to the image to the appropriate mode array
                $imagePath = storage_path('app/public/' . $imageExposure->path);
                if ($currentExposureProgram === $firstExposureProgram) {
                    $groupedExposures[$groupName]['mode_one'][] = $imagePath;
                } else {
                    $groupedExposures[$groupName]['mode_two'][] = $imagePath;
                }

                $previousExposureProgram = $currentExposureProgram;
            }

            return $groupedExposures;
        }
    }
</CODE>


>> ## FLAMBIENT IMAGE SCRIPT "ALPHA" THERE WILL BE MORE OF THESE, SO WELL NEED A WAY TO MODIFY AND ADD SCRIPTS AS WELL AS A WAY TO RE-RUN ON THE **''ExposureStack''**, POSSIBLY A IMAGE DASHBOARD.

 >   CURRENT bash script to generate the ImageMagich scrip to make the flambient picture.
 

# This script is designed to create a FLAMBIENT image from a set of images.
# The script will take an ambient image and flash images and combine them to create a FLAMBIENT image. The script will use ImageMagick to create the FLAMBIENT image.


 <CODE> 

}

        function setup_variables() {
            export SHEBANG='#!/bin/env magick-script'
            export IMG_PATH="public/storage/images/SESSION_ID"
            export OUTPUT_FILE="generateSequenceNumber.jpeg"
            export SCRIPT_PATH="public/storage/SESSION_ID/generateSequenceNumber.mgk"
            export OUTPUT_DIR="FlambientPictures"
}

        function setup_environment() {

            mkdir -p $IMG_PATH/$OUTPUT_DIR

        function create_ambient_mask() {
            cat << EOF > script.mgk
            $SHEBANG
            $AMBIENT -channel B -separate -level 80%,100%,5.0 -write mpr:ambient_mask_alpha +delete
            EOF
            }

        function merge_flash_images() {
            cat << EOF >> script.mgk
            $FLASH[1] $FLASH[2] $FLASH[3]  -compose lighten -composite -write mpr:flash_merge +delete
            EOF
            }

        function create_highlight_mask() {
            cat << EOF >> script.mgk
            mpr:flash_merge mpr:ambient_mask_alpha -compose CopyOpacity -composite -write mpr:flash_mask +delete
            EOF
            }

        function create_luminized_image() {
            cat << EOF >> script.mgk
            mpr:flash_merge $AMBIENT -compose Luminize -composite -write mpr:luminize_flambient +delete
            EOF
            }

        function create_overlay() {
            cat << EOF >> script.mgk
            mpr:luminize_flambient mpr:flash_mask -compose Over -composite -write mpr:ungraded_flambient +delete
            EOF
            }

        function create_final_image() {
            cat << EOF >> script.mgk
            mpr:flash_merge mpr:ungraded_flambient -compose Colorize -composite -write $IMG_PATH/$OUTPUT_DIR/$OUTPUT_FILE
            EOF
            }

        function main() {
            setup_variables
            setup_environment
            check_mpr
            create_ambient_mask
            merge_flash_images
            create_highlight_mask
            create_luminized_image
            create_overlay
            create_final_image
        }

        main

    magick -script script.mgk

 </CODE>


>>Proposed Project Structure
> Directory Structure

 project-root/
├── app/
│   ├── Console/
│   ├── Exceptions/
│   ├── Http/
│   ├── Models/
│   ├── Services/
│   │   ├── ImageProcessingService.php
│   │   └── ImageGroupingService.php
│   ├── Providers/
│   └── ...
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   ├── storage/
│   │   ├── images/
│   │   │   └── SESSION_ID/
│   │   └── ...
│   └── ...
├── resources/
│   ├── views/
│   └── ...
├── routes/
│   └── web.php
├── storage/
├── tests/
├── .env
├── artisan
└── composer.json



1. # ImageGroupingService
<CODE>

namespace App\Services;

use Illuminate\Support\Collection;

class ImageGroupingService
{
    public function groupImageExposures(Collection $imageExposures, string $metaDelineation): array
    {
        $groupedExposures = [];
        $groupNumber = 1;
        $firstMetaValue = null;
        $previousMetaValue = null;

        foreach ($imageExposures->sortBy('sequence_column') as $imageExposure) {
            $currentMetaValue = $imageExposure->$metaDelineation;

            if ($firstMetaValue === null) {
                $firstMetaValue = $currentMetaValue;
            } elseif ($currentMetaValue === $firstMetaValue && $currentMetaValue !== $previousMetaValue) {
                $groupNumber++;
            }

            $groupName = "Flash / Ambient $groupNumber";
            if (!isset($groupedExposures[$groupName])) {
                $groupedExposures[$groupName] = [
                    'mode_one'  => [],
                    'mode_two'  => [],
                    'exposures' => [],
                ];
            }

            if ($currentMetaValue === $firstMetaValue) {
                $groupedExposures[$groupName]['mode_one'][] = $imageExposure->filename;
            } else {
                $groupedExposures[$groupName]['mode_two'][] = $imageExposure->filename;
            }

            $groupedExposures[$groupName]['exposures'][] = $imageExposure;

            $previousMetaValue = $currentMetaValue;
        }

        return $groupedExposures;
    }
}
</CODE>

2. # ImageProcessingService
<CODE>
namespace App\Services;

class ImageProcessingService
{
    public function generateFlambientScript($ambientImage, $flashImages, $outputFile, $scriptPath)
    {
        $scriptContent = <<<EOT
#!/bin/env magick-script
$ambientImage -channel B -separate -level 80%,100%,5.0 -write mpr:ambient_mask_alpha +delete
$flashImages[0] $flashImages[1] $flashImages[2] -compose lighten -composite -write mpr:flash_merge +delete
mpr:flash_merge mpr:ambient_mask_alpha -compose CopyOpacity -composite -write mpr:flash_mask +delete
mpr:flash_merge $ambientImage -compose Luminize -composite -write mpr:luminize_flambient +delete
mpr:luminize_flambient mpr:flash_mask -compose Over -composite -write mpr:ungraded_flambient +delete
mpr:flash_merge mpr:ungraded_flambient -compose Colorize -composite -write $outputFile
EOT;

        file_put_contents($scriptPath, $scriptContent);
    }

    public function executeScript($scriptPath)
    {
        exec("magick -script $scriptPath");
    }
}

</CODE>

3. # Migration for Images Table
<CODE>
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->integer('exposure_program')->nullable();
            $table->integer('iso_speed_ratings')->nullable();
            $table->integer('shutter_speed_value')->nullable();
            $table->integer('aperture_value')->nullable();
            $table->integer('f_number')->nullable();
            $table->integer('flash')->nullable();
            $table->integer('exposure_mode')->nullable();
            $table->integer('white_balance')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('images');
    }
}

</CODE>

4. # Routes
<CODE>
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImageController;

Route::get('/', [ImageController::class, 'index'])->name('home');
Route::post('/upload', [ImageController::class, 'upload'])->name('upload');
Route::get('/group', [ImageController::class, 'group'])->name('group');
Route::post('/process', [ImageController::class, 'process'])->name('process');

</CODE>

5. # ImageController
<CODE>
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Image;
use App\Services\ImageGroupingService;
use App\Services\ImageProcessingService;

class ImageController extends Controller
{
    protected $imageGroupingService;
    protected $imageProcessingService;

    public function __construct(ImageGroupingService $imageGroupingService, ImageProcessingService $imageProcessingService)
    {
        $this->imageGroupingService = $imageGroupingService;
        $this->imageProcessingService = $imageProcessingService;
    }

    public function index()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        // Handle image upload and store metadata
    }

    public function group(Request $request)
    {
        $images = Image::all();
        $groupedImages = $this->imageGroupingService->groupImageExposures($images, $request->get('metaDelineation'));

        return view('group', compact('groupedImages'));
    }

    public function process(Request $request)
    {
        $groupedImages = $request->get('groupedImages');

        foreach ($groupedImages as $group) {
            $ambientImage = $group['mode_one'][0];
            $flashImages = $group['mode_two'];
            $outputFile = "public/storage/flambient/{$group['groupName']}.jpeg";
            $scriptPath = "public/storage/scripts/{$group['groupName']}.mgk";

            $this->imageProcessingService->generateFlambientScript($ambientImage, $flashImages, $outputFile, $scriptPath);
            $this->imageProcessingService->executeScript($scriptPath);
        }

        return view('result');
    }
}

</CODE>

6. # Laravel Models

<CODE>
    namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'exposure_program',
        'iso_speed_ratings',
        'shutter_speed_value',
        'aperture_value',
        'f_number',
        'exposure_program',
        'flash',
        'exposure_mode',
        'white_balance',
    ];

    // Add relationships and other model methods here as needed
} 

</CODE>

# Blade Templates
1. upload.blade.php: For image upload form.
2. group.blade.php: For displaying grouped images and selecting MetaDelineation.
3. result.blade.php: For displaying the final flambient images and download options.

# SUGGESTED IMPROVEMENTS

 - a) Error Handling & Validation
    1. Add to ImageProcessingService

<CODE>    
public function generateFlambientScript($ambientImage, $flashImages, $outputFile, $scriptPath)
{
    // Add validation
    if (empty($flashImages)) {
        throw new \InvalidArgumentException('Flash images are required');
    }
    if (!file_exists($ambientImage)) {
        throw new \RuntimeException('Ambient image not found');
    }
    // ... rest of your code
}
</CODE>

# Queue Processing

2. Adding job queues for processing large batches of images:

<CODE>
namespace App\Jobs;

class ProcessFlambientImage implements ShouldQueue
{
    protected $ambientImage;
    protected $flashImages;
    protected $outputFile;
    
    public function handle(ImageProcessingService $processor)
    {
        $processor->generateFlambientScript(...);
        $processor->executeScript(...);
    }
}
</CODE>

# Session Management

1. Add session handling for managing upload states
2. Implement cleanup jobs for expired sessions
3. Security Considerations
4. Add file type validation
5. Implement rate limiting for uploads
6. Add secure download tokens for processed images

# Progress Tracking

<CODE>
// Add to Image model
protected $appends = ['processing_status'];

public function getProcessingStatusAttribute()
{
    return Cache::get("image_processing_{$this->id}", 'pending');
}
</CODE>


# Preview Generation

Add thumbnail generation for the review step
Implement image preview before final processing


# Database Optimization

1. Add indexes in migration

<CODE>  
$table->index('exposure_program');
$table->index('iso_speed_ratings');
$table->index(['filename', 'exposure_program']);
</CODE>


# MetaDelineation Implementation - create an enum or config file for MetaDelineation types:
<CODE>
// config/meta-delineation.php
return [
    'types' => [
        'iso_speed_ratings' => [
            'name' => 'ISO Speed',
            'field' => 'iso_speed_ratings',
        ],
        'shutter_speed_value' => [
            'name' => 'Shutter Speed',
            'field' => 'shutter_speed_value',
        ],
        // ... other types
    ]
];
</CODE>

# Monitoring & Logging
1. Add logging for processing steps
2. Implement monitoring for failed processes
3. Testing Strategy Add these test categories:

<CODE>

// tests/Feature/ImageProcessingTest.php
public function test_image_grouping()
{
    // Test grouping logic
}

public function test_flambient_processing()
{
    // Test processing logic
}

public function test_meta_delineation_selection()
{
    // Test meta selection
}
</CODE>

# Frontend Considerations
1. Add loading states for processing
2. Implement drag-and-drop reordering & deletion of images on first view, before processing. 
3. Add preview capabilities

# Scaling Considerations
1. Implement caching for processed images
2. Consider using cloud storage for image processing


# Payment Implemation

Payment should be as simple as possible, just a button under each process images that will allow for an individual image purchase, as well as a button at the bottom to purchase all images for the discounted price.

<CODE>
//? Stripe PHP library installed
\Stripe\Stripe::setApiKey('your_secret_key');

$amount = 150; // Total amount in cents
$paymentLink = \Stripe\PaymentLink::create([
    'line_items' => [[
        'price_data' => [
            'currency' => 'usd',
              'product_data' => [
                'name' => 'Image Purchase',
            ],
            'unit_amount' => $amount,
        ],
        'quantity' => 1,
    ]],
    'mode' => 'payment',
]);

// Redirect user to the payment link
header("Location: {$paymentLink->url}");
exit();
</CODE>