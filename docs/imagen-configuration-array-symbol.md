# `imagen` is a configuration array in `flambient.php` that defines settings for integrating with the Imagen AI API.  
It holds API credentials, request behavior, and polling logic used to manage AI-powered photo editing workflows.

---

### Definition

The `imagen` symbol is a top-level key in the `flambient.php` configuration file, returning an associative array of settings used to interact with the **Imagen AI** service—an external API for automated image editing, likely tailored for professional photography workflows.

```php
9:17:/Users/davepeloso/Projects/flambient/config/flambient.php
'imagen' => [
    'api_key' => env('IMAGEN_AI_API_KEY'),
    'base_url' => env('IMAGEN_API_BASE_URL', 'https://api-beta.imagen-ai.com/v1'),
    'profile_key' => env('IMAGEN_PROFILE_KEY', 309406),
    'timeout' => env('IMAGEN_TIMEOUT', 30),
    'retry_times' => env('IMAGEN_RETRY_TIMES', 3),
    'poll_interval' => env('IMAGEN_POLL_INTERVAL', 30),
    'poll_max_attempts' => env('IMAGEN_POLL_MAX_ATTEMPTS', 240),
],
```

- **Type**: `array<string, mixed>`
- **Scope**: Global configuration (Laravel-style)
- **Environment-driven**: All values use `env()` for secure, flexible deployment
- **Purpose**: Centralizes communication parameters for the Imagen AI service

The configuration supports fallback defaults (e.g., `'base_url'` defaults to the beta endpoint) and is designed for resilience—retries failed requests and uses polling to track long-running AI editing jobs.

---

### Example Usages

While no direct caller references were found in the current workspace scan, the presence of a full SDK (`imagen-ai-sdk-master/`) and environment variables suggests this config is consumed by a custom integration layer—likely a Laravel service or wrapper around the Python SDK.

A typical usage pattern would involve:

1. Reading `config('flambient.imagen')` in a service class
2. Initializing a client (possibly via shell execution of the Python SDK)
3. Triggering AI edits using the provided API key and profile

Given the SDK examples like `quick_start.py`, we can infer intended usage:

```python
12:/Users/davepeloso/Projects/flambient/imagen-ai-sdk-master/examples/quick_start.py
from imagen_sdk import quick_edit

# Hypothetical integration using config values from PHP
await quick_edit(
    api_key=config('flambient.imagen.api_key'),
    profile_key=config('flambient.imagen.profile_key'),
    images=['photo1.jpg', 'photo2.jpg']
)
```

Despite 24 files referencing "Imagen" (including SDK, examples, and docs), no PHP code currently calls into `imagen`—indicating either:
- The integration is not yet implemented
- It's invoked indirectly (e.g., via CLI or HTTP calls from frontend)
- Usage occurs outside the scanned workspace

---

### Notes

- The `'profile_key'` defaults to `309406`, a non-obvious hardcoded value likely referencing a specific AI editing preset (e.g., “Wedding Portrait - Natural Light”) on the Imagen platform.
- Polling is configured for up to **240 attempts at 30-second intervals**, allowing a maximum wait time of **2 hours**—indicative of long-running batch AI processing jobs.
- The base URL points to a **beta API endpoint**, suggesting this integration is still in active development or testing.

---

### See Also

- `IMAGEN_AI_API_KEY`: Environment variable that securely injects the API key; required for authentication.
- `imagen-ai-sdk-master/`: Full Python SDK found in the project, likely used in conjunction with this config.
- `workflow.storage_path`: Configures where processed images are stored locally after download from Imagen.
- `polling`: General polling settings that may complement or override `imagen.poll_interval` in broader system contexts.