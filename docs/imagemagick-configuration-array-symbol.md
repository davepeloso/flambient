# `imagemagick` is a configuration array in `flambient.php` that defines settings for local image processing using the ImageMagick tool.  
It enables automated blending of ambient and flash exposures in real estate photography workflows via CLI execution.

---

### Definition

The `imagemagick` symbol is a top-level key in the `flambient.php` configuration file, containing parameters used by the application’s local image processing service. These settings are primarily consumed by the `ImageMagickService` class (inferred from usage in tests and workflow steps) to generate and execute ImageMagick commands for blending photo pairs.

```php
24:32:/Users/davepeloso/Projects/flambient/config/flambient.php
'imagemagick' => [
    'binary' => env('IMAGEMAGICK_BINARY', 'magick'),
    'level_low' => env('IMAGEMAGICK_LEVEL_LOW', '40%'),
    'level_high' => env('IMAGEMAGICK_LEVEL_HIGH', '140%'),
    'gamma' => env('IMAGEMAGICK_GAMMA', '1.0'),
    'output_prefix' => env('IMAGEMAGICK_OUTPUT_PREFIX', 'flambient'),
    'enable_darken_export' => env('IMAGEMAGICK_DARKEN_EXPORT', true),
    'darken_suffix' => env('IMAGEMAGICK_DARKEN_SUFFIX', '_tmp'),
],
```

- **Type**: Associative array (configuration block)
- **Scope**: Application-level config, accessible via `config('flambient.imagemagick')`
- **Source**: Defined in `/config/flambient.php`, values overridden by `.env` variables
- **Purpose**: Controls behavior of ImageMagick-based blending scripts during Step 3 of the workflow

This configuration directly influences how dual-exposure images (ambient + flash) are combined using local processing before optional upload to Imagen AI.

---

### Example Usages

In the Flambient workflow, the `imagemagick` config is used when executing the `--local` mode of the main command. It drives the generation of `.mgk` script files that ImageMagick executes to blend image pairs.

For example, during local processing:

```bash
php artisan flambient:process --dir="public/123-main-street" --local
```

This triggers Step 3: **Processing with ImageMagick**, where the application:
- Reads EXIF-classified ambient/flash image pairs
- Generates an ImageMagick script using values like `level_low`, `level_high`, and `gamma`
- Executes the script via the `magick` binary
- Outputs blended images prefixed with `flambient_`

The actual blending logic uses a mask derived from the ambient image's luminance, adjusted by `level_low` and `level_high` thresholds, while `gamma` corrects brightness in intermediate composites.

From test logs and workflow output:
```text
Step 3/3: Processing with ImageMagick
⠧ Generating and executing ImageMagick scripts...
✓ Created 27 blended images in 8.5s
```

The `imagemagick` config is central to this step and is referenced throughout the codebase in:
- `ImageMagickServiceTest.php` – unit tests validating script generation
- `.env.example` – environment variable definitions
- `README.md` – documentation of supported settings and CLI behavior

It is not directly called in code but accessed via Laravel’s `config()` helper, making it a passive configuration dependency rather than an active service.

---

### Notes

- Despite its name, `imagemagick` does not refer to the software itself but to the **application-specific settings** for invoking it.
- The default `binary` value is `magick`, which corresponds to ImageMagick 7+; older systems using `convert` would need to override this via `.env`.
- The `enable_darken_export` setting controls whether a secondary darkened version of the flash image is saved (used for debugging or alternate blending strategies), though it defaults to `true` even if not always utilized in production.

---

### See Also

- `ImageMagickService`: The service class that consumes this config to build and run ImageMagick scripts; responsible for Step 3 in the workflow.
- `IMAGEMAGICK_BINARY`: Environment variable allowing override of the executable path; useful in CI or non-standard installations.
- `flambient.workflow`: Controls overall execution flow; determines whether ImageMagick processing runs locally or skips to cloud enhancement.
- `exiftool`: Companion tool used earlier in the workflow to classify images before they reach the ImageMagick stage.