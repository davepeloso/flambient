<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Batch;
use App\Models\Image;
use App\Models\ExposureStack;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\select;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\error;
use function Laravel\Prompts\table;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\search;
use function Laravel\Prompts\multiselect;

class HomeCommand extends Command
{
    protected $signature = 'home 
        {--no-ascii : Skip the ASCII art header}
        {--compact : Use compact header only}';
    
    protected $description = 'Interactive dashboard for the D-MEC Image Processing Application';

    private string $version = '2.1.0';
    private string $codename = 'Flazsh Revival';

    public function handle(): int
    {
        $this->showHeader();
        $this->mainMenu();
        return 0;
    }

    // ═══════════════════════════════════════════════════════════
    // HEADER & BRANDING
    // ═══════════════════════════════════════════════════════════

    private function showHeader(): void
    {
        if ($this->option('compact')) {
            $this->showCompactHeader();
            return;
        }

        if ($this->option('no-ascii')) {
            $this->showMinimalHeader();
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>   █████▒██▓    ▄▄▄           ▒███████▒  ██████  ██░ ██</>');
        $this->line('<fg=cyan> ▓██   ▒▓██▒   ▒████▄         ▒ ▒ ▒ ▄▀░▒██    ▒ ▓██░ ██▒</>');
        $this->line('<fg=cyan> ▒████ ░▒██░   ▒██  ▀█▄       ░ ▒ ▄▀▒░ ░ ▓██▄   ▒██▀▀██░</>');
        $this->line('<fg=cyan> ░▓█▒  ░▒██░   ░██▄▄▄▄██        ▄▀▒   ░  ▒   ██▒░▓█ ░██</>');
        $this->line('<fg=cyan> ░▒█░   ░██████▒▓█   ▓██▒ ██▓ ▒███████▒▒██████▒▒░▓█▒░██▓</>');
        $this->line('<fg=blue>  ▒ ░   ░ ▒░▓  ░▒▒   ▓▒█░ ▒▓▒ ░▒▒ ▓░▒░▒▒ ▒▓▒ ▒ ░ ▒ ░░▒░▒</>');
        $this->line('<fg=blue>  ░     ░ ░ ▒  ░ ▒   ▒▒ ░ ░▒  ░░▒ ▒ ░ ▒░ ░▒  ░ ░ ▒ ░▒░ ░</>');
        $this->line('<fg=blue>  ░ ░     ░ ░    ░   ▒    ░   ░ ░ ░ ░ ░░  ░  ░   ░  ░░ ░</>');
        $this->line('<fg=blue>            ░  ░     ░  ░  ░    ░ ░          ░   ░  ░  ░</>');
        $this->line('<fg=blue>                           ░  ░</>');
        $this->newLine();
        $this->line('<fg=white>═══════════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  D-MEC IMAGE PROCESSOR</> <fg=gray>│</> <fg=white>v' . $this->version . '</> <fg=gray>│</> <fg=green>Laravel Edition</>');
        $this->line('<fg=gray>  "' . $this->codename . '"</>');
        $this->line('<fg=white>═══════════════════════════════════════════════════════════════</>');
        $this->showSystemInfo();
        $this->line('<fg=white>═══════════════════════════════════════════════════════════════</>');
        $this->newLine();
    }

    private function showSystemInfo(): void
    {
        $this->line('<fg=gray>  Date:</> ' . now()->format('l, F j, Y \a\t g:i A'));
        $this->line('<fg=gray>  User:</> ' . get_current_user());
        $this->line('<fg=gray>  Path:</> ' . base_path());
        
        // Quick status indicators
        $imagenStatus = $this->getImagenStatus();
        $dbStatus = $this->getDatabaseStatus();
        
        $this->line('<fg=gray>  Status:</> ' . 
            '<fg=' . ($imagenStatus ? 'green' : 'red') . '>● Imagen</> ' .
            '<fg=' . ($dbStatus ? 'green' : 'red') . '>● Database</> ' .
            '<fg=green>● System</>');
    }

    private function showCompactHeader(): void
    {
        intro('D-MEC Image Processor v' . $this->version . ' - ' . $this->codename);
    }

    private function showMinimalHeader(): void
    {
        $this->line('');
        $this->line('<fg=cyan>FL4.ZSH</> <fg=gray>-</> D-MEC Image Processor v' . $this->version);
        $this->line('');
    }

    private function getImagenStatus(): bool
    {
        return !empty(env('IMAGEN_AI_API_KEY'));
    }

    private function getDatabaseStatus(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // MAIN MENU
    // ═══════════════════════════════════════════════════════════

    private function mainMenu(): void
    {
        while (true) {
            $choice = select(
                label: '🏠 Main Menu',
                options: [
                    'process' => '🖼️  Image Processing',
                    'imagen' => '☁️  Imagen AI Integration',
                    'database' => '📊 Database & Jobs',
                    'tether' => '📷 Camera Tethering & Import',
                    'manual' => '📖 Manual & Documentation',
                    'debug' => '🔧 Debug & Diagnostics',
                    'settings' => '⚙️  Settings & Configuration',
                    'quit' => '🚪 Exit Application',
                ],
                default: 'process',
                hint: 'Use arrow keys to navigate, Enter to select'
            );

            match ($choice) {
                'process' => $this->imageProcessingMenu(),
                'imagen' => $this->imagenMenu(),
                'database' => $this->databaseMenu(),
                'tether' => $this->tetheringMenu(),
                'manual' => $this->manualMenu(),
                'debug' => $this->debugMenu(),
                'settings' => $this->settingsMenu(),
                'quit' => $this->exitApp(),
            };
        }
    }

    // ═══════════════════════════════════════════════════════════
    // IMAGE PROCESSING MENU
    // ═══════════════════════════════════════════════════════════

    private function imageProcessingMenu(): void
    {
        $choice = select(
            label: '🖼️  Image Processing',
            options: [
                'flambient' => '🔥 Run Flambient Workflow',
                'upload' => '📤 Upload & Extract EXIF',
                'classify' => '🏷️  Classify Exposure Stacks',
                'generate' => '📝 Generate ImageMagick Scripts',
                'execute' => '▶️  Execute Processing Scripts',
                'reprocess' => '🔄 Reprocess EXIF Data',
                'browse' => '📁 Browse Output Folders',
                'back' => '← Back to Main Menu',
            ],
            hint: 'Select a processing operation'
        );

        match ($choice) {
            'flambient' => $this->runFlambientWorkflow(),
            'upload' => $this->runUploadWorkflow(),
            'classify' => $this->runClassifyWorkflow(),
            'generate' => $this->runGenerateScripts(),
            'execute' => $this->runExecuteScripts(),
            'reprocess' => $this->call('flambient:reprocess-exif'),
            'browse' => $this->browseOutputFolders(),
            'back' => null,
        };
    }

    private function runFlambientWorkflow(): void
    {
        $mode = select(
            label: 'Select processing mode',
            options: [
                'local' => 'Local Only (ImageMagick blending)',
                'full' => 'Full Workflow (with Imagen AI)',
                'interactive' => 'Interactive (guided prompts)',
            ]
        );

        $flags = match ($mode) {
            'local' => '--local',
            'full' => '',
            'interactive' => '',
        };

        info('Starting Flambient workflow...');
        $this->call('flambient:process', $flags ? ['--local' => true] : []);
    }

    private function runUploadWorkflow(): void
    {
        info('Opening web upload interface...');
        $url = config('app.url') . '/upload';
        note("Navigate to: {$url}");
        
        if (confirm('Open in browser?', default: true)) {
            Process::run("open '{$url}'");
        }
    }

    private function runClassifyWorkflow(): void
    {
        info('Opening cull/classify interface...');
        $url = config('app.url') . '/cull';
        note("Navigate to: {$url}");
        
        if (confirm('Open in browser?', default: true)) {
            Process::run("open '{$url}'");
        }
    }

    private function runGenerateScripts(): void
    {
        $batches = Batch::where('status', 'pending')->orWhere('status', 'classified')->get();
        
        if ($batches->isEmpty()) {
            warning('No batches ready for script generation.');
            note('Upload and classify images first.');
            return;
        }

        $selected = select(
            label: 'Select batch for script generation',
            options: $batches->mapWithKeys(fn($b) => [
                $b->id => substr($b->id, 0, 8) . "... ({$b->image_count} images)"
            ])->toArray()
        );

        info("Generating scripts for batch: {$selected}");
        warning('Script generation command not yet fully implemented.');
        note('This will generate ImageMagick .mgk scripts based on your exposure stacks.');
    }

    private function runExecuteScripts(): void
    {
        $scriptDir = storage_path('flambient');
        
        if (!is_dir($scriptDir)) {
            warning('No processing scripts found.');
            note('Generate scripts first via Image Processing → Generate ImageMagick Scripts');
            return;
        }

        $projects = collect(File::directories($scriptDir))->map(fn($p) => basename($p));
        
        if ($projects->isEmpty()) {
            warning('No project folders found.');
            return;
        }

        $selected = select(
            label: 'Select project to execute scripts',
            options: $projects->combine($projects)->toArray()
        );

        info("Executing scripts for: {$selected}");
        warning('Script execution command not yet fully implemented.');
    }

    private function browseOutputFolders(): void
    {
        $outputDir = storage_path('flambient');
        
        if (!is_dir($outputDir)) {
            warning('No output folders exist yet.');
            return;
        }

        info("Opening: {$outputDir}");
        Process::run("open '{$outputDir}'");
    }

    // ═══════════════════════════════════════════════════════════
    // IMAGEN AI MENU
    // ═══════════════════════════════════════════════════════════

    private function imagenMenu(): void
    {
        if (!$this->getImagenStatus()) {
            error('Imagen AI is not configured.');
            note('Set IMAGEN_AI_API_KEY in your .env file');
            if (confirm('Configure now?')) {
                $this->editEnvFile();
            }
            return;
        }

        $choice = select(
            label: '☁️  Imagen AI Integration',
            options: [
                'profiles' => '📋 List Available Profiles',
                'projects' => '📁 View Recent Projects',
                'status' => '🔍 Check Job Status (by UUID)',
                'local_jobs' => '💾 View Local Job Records',
                'upload' => '📤 Upload to Imagen',
                'export' => '📥 Export & Download Results',
                'config' => '⚙️  View Imagen Config',
                'api_test' => '🧪 Test API Endpoints',
                'back' => '← Back to Main Menu',
            ],
        );

        match ($choice) {
            'profiles' => $this->listImagenProfiles(),
            'projects' => $this->listImagenProjects(),
            'status' => $this->checkImagenJobStatus(),
            'local_jobs' => $this->viewLocalImagenJobs(),
            'upload' => $this->uploadToImagen(),
            'export' => $this->exportFromImagen(),
            'config' => $this->showImagenConfig(),
            'api_test' => $this->testImagenApiEndpoints(),
            'back' => null,
        };
    }

    private function listImagenProfiles(): void
    {
        $apiKey = $this->getImagenApiKey();
        $baseUrl = $this->getImagenBaseUrl();

        $result = spin(
            callback: function () use ($apiKey, $baseUrl) {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->get("{$baseUrl}/profiles/");
                return $response->json();
            },
            message: 'Fetching Imagen AI profiles...'
        );

        if (isset($result['data'])) {
            $profiles = collect($result['data'])->map(function ($profile) {
                return [
                    'Key' => $profile['profile_key'] ?? 'N/A',
                    'Name' => $profile['profile_name'] ?? 'Unnamed',
                    'Type' => $profile['photography_type'] ?? 'Unknown',
                    'Created' => isset($profile['created_at']) 
                        ? date('Y-m-d', strtotime($profile['created_at'])) 
                        : 'N/A',
                ];
            })->toArray();

            table(['Key', 'Name', 'Type', 'Created'], $profiles);
            note('Use these profile keys in your .env IMAGEN_PROFILE_KEY setting');
        } else {
            error('Failed to fetch profiles.');
            $this->line('Response: ' . json_encode($result));
        }
    }

    private function listImagenProjects(): void
    {
        $apiKey = $this->getImagenApiKey();
        $baseUrl = $this->getImagenBaseUrl();

        $result = spin(
            callback: function () use ($apiKey, $baseUrl) {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->get("{$baseUrl}/projects/");
                return $response->json();
            },
            message: 'Fetching Imagen AI projects...'
        );

        if (isset($result['data']['projects'])) {
            $projects = collect($result['data']['projects'])->take(15)->map(function ($project) {
                $status = $project['project_status'] ?? 'Unknown';
                $statusIcon = match($status) {
                    'completed' => '✅',
                    'processing' => '⏳',
                    'failed' => '❌',
                    'pending' => '🕐',
                    default => '❓'
                };
                
                return [
                    'UUID' => substr($project['project_uuid'] ?? '', 0, 8) . '...',
                    'Name' => substr($project['project_name'] ?? 'Unnamed', 0, 25),
                    'Status' => $statusIcon . ' ' . $status,
                    'Images' => $project['number_of_photos'] ?? 0,
                    'Created' => isset($project['created_at']) 
                        ? date('Y-m-d H:i', strtotime($project['created_at'])) 
                        : 'N/A',
                ];
            })->toArray();

            table(['UUID', 'Name', 'Status', 'Images', 'Created'], $projects);
            note('Showing last 15 projects. Use "Check Job Status" to view details.');
        } else {
            warning('No projects found or API returned unexpected format.');
        }
    }

    private function checkImagenJobStatus(): void
    {
        $uuid = text(
            label: 'Enter Project UUID',
            placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            required: true,
            validate: fn($v) => strlen($v) < 10 ? 'UUID too short' : null
        );

        $this->fetchAndDisplayProjectStatus($uuid);
    }

    private function fetchAndDisplayProjectStatus(string $uuid): void
    {
        $apiKey = $this->getImagenApiKey();
        $baseUrl = $this->getImagenBaseUrl();

        // Fetch project details
        $projectResult = spin(
            callback: function () use ($apiKey, $baseUrl, $uuid) {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->get("{$baseUrl}/projects/{$uuid}");
                return $response->json();
            },
            message: 'Fetching project details...'
        );

        // Fetch edit status
        $statusResult = spin(
            callback: function () use ($apiKey, $baseUrl, $uuid) {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->get("{$baseUrl}/projects/{$uuid}/edit/status");
                return $response->json();
            },
            message: 'Fetching edit status...'
        );

        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  PROJECT DETAILS</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        
        if (isset($projectResult['data'])) {
            $project = $projectResult['data'];
            $this->line("<fg=gray>UUID:</> {$uuid}");
            $this->line("<fg=gray>Name:</> " . ($project['project_name'] ?? 'Unnamed'));
            $this->line("<fg=gray>Status:</> " . ($project['project_status'] ?? 'Unknown'));
            $this->line("<fg=gray>Images:</> " . ($project['number_of_photos'] ?? 0));
        }

        if (isset($statusResult['data'])) {
            $data = $statusResult['data'];
            $this->line('');
            $this->line('<fg=yellow>Edit Status:</>');
            $this->line("  <fg=gray>Status:</> {$data['status']}");
            $this->line("  <fg=gray>Progress:</> {$data['progress']}%");
            
            if (isset($data['error_message']) && $data['error_message']) {
                error("  Error: {$data['error_message']}");
            }
        }
        
        $this->line('');
    }

    private function viewLocalImagenJobs(): void
    {
        // Check if imagen_jobs table exists
        try {
            $jobs = DB::table('imagen_jobs')->latest()->take(20)->get();
            
            if ($jobs->isEmpty()) {
                warning('No local Imagen job records found.');
                note('Jobs are recorded when you process images through the workflow.');
                return;
            }

            $data = $jobs->map(function ($job) {
                $statusIcon = match($job->status ?? 'unknown') {
                    'completed' => '✅',
                    'processing', 'uploading', 'polling' => '⏳',
                    'failed' => '❌',
                    'pending' => '🕐',
                    default => '❓'
                };

                return [
                    'ID' => $job->id,
                    'UUID' => substr($job->project_uuid ?? '', 0, 8) . '...',
                    'Status' => $statusIcon . ' ' . ($job->status ?? 'unknown'),
                    'Images' => $job->image_count ?? 0,
                    'Created' => date('Y-m-d H:i', strtotime($job->created_at)),
                ];
            })->toArray();

            table(['ID', 'UUID', 'Status', 'Images', 'Created'], $data);
            
        } catch (\Exception $e) {
            warning('Imagen jobs table not found in database.');
            note('The imagen_jobs table may not have been created yet.');
            note('Run migrations or the first Imagen workflow to create it.');
            
            if (confirm('View standard Laravel jobs queue instead?')) {
                $this->viewJobs();
            }
        }
    }

    private function uploadToImagen(): void
    {
        $folders = $this->getOutputFolders();
        
        if (empty($folders)) {
            warning('No processed folders available for upload.');
            note('Run the Flambient workflow first to generate blended images.');
            return;
        }

        $selected = select(
            label: 'Select folder to upload',
            options: $folders
        );

        $profileKey = text(
            label: 'Imagen Profile Key',
            default: env('IMAGEN_PROFILE_KEY', '309406'),
            required: true
        );

        if (!confirm("Upload images from {$selected} using profile {$profileKey}?")) {
            return;
        }

        info("Starting upload to Imagen AI...");
        warning('Full upload implementation requires ImagenClient service.');
        note("Would upload from: {$selected}");
        note("With profile: {$profileKey}");
    }

    private function exportFromImagen(): void
    {
        $uuid = text(
            label: 'Enter Project UUID to export',
            placeholder: 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            required: true
        );

        $apiKey = $this->getImagenApiKey();
        $baseUrl = $this->getImagenBaseUrl();

        // Trigger export
        $result = spin(
            callback: function () use ($apiKey, $baseUrl, $uuid) {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->post("{$baseUrl}/projects/{$uuid}/export_to_jpeg");
                return $response;
            },
            message: 'Triggering export...'
        );

        if ($result->successful()) {
            info('✓ Export triggered successfully!');
            note('Wait a moment, then use the API to fetch download links.');
            
            if (confirm('Fetch download links now? (Wait 10 seconds)')) {
                sleep(10);
                $this->fetchExportLinks($uuid);
            }
        } else {
            error('Export failed: ' . $result->body());
        }
    }

    private function fetchExportLinks(string $uuid): void
    {
        $apiKey = $this->getImagenApiKey();
        $baseUrl = $this->getImagenBaseUrl();

        $result = spin(
            callback: function () use ($apiKey, $baseUrl, $uuid) {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->get("{$baseUrl}/projects/{$uuid}/get_export_download_links");
                return $response->json();
            },
            message: 'Fetching download links...'
        );

        if (isset($result['data']['files_list'])) {
            $files = $result['data']['files_list'];
            info('✓ Found ' . count($files) . ' files ready for download');
            
            foreach (array_slice($files, 0, 5) as $file) {
                note("  - " . ($file['file_name'] ?? 'unknown'));
            }
            
            if (count($files) > 5) {
                note("  ... and " . (count($files) - 5) . " more");
            }
        } else {
            warning('No download links available yet. Try again in a moment.');
        }
    }

    private function showImagenConfig(): void
    {
        $config = [
            ['Setting', 'Value', 'Status'],
            [
                'API Key', 
                env('IMAGEN_AI_API_KEY') ? '••••••' . substr(env('IMAGEN_AI_API_KEY'), -4) : 'Not Set',
                env('IMAGEN_AI_API_KEY') ? '✓' : '✗'
            ],
            ['Base URL', env('IMAGEN_API_BASE_URL', 'https://api-beta.imagen-ai.com/v1'), '✓'],
            ['Profile Key', env('IMAGEN_PROFILE_KEY', '309406'), '✓'],
            ['Timeout', env('IMAGEN_TIMEOUT', '30') . 's', '✓'],
            ['Poll Interval', env('IMAGEN_POLL_INTERVAL', '30') . 's', '✓'],
            ['Max Poll Attempts', env('IMAGEN_POLL_MAX_ATTEMPTS', '240'), '✓'],
        ];

        table(['Setting', 'Value', 'Status'], array_slice($config, 1));
    }

    private function testImagenApiEndpoints(): void
    {
        $endpoints = select(
            label: 'Select endpoint to test',
            options: [
                'profiles' => 'GET /profiles/ - List profiles',
                'projects' => 'GET /projects/ - List projects',
                'account' => 'GET /account/ - Account info',
                'back' => '← Back',
            ]
        );

        if ($endpoints === 'back') return;

        $apiKey = $this->getImagenApiKey();
        $baseUrl = $this->getImagenBaseUrl();

        $endpoint = match($endpoints) {
            'profiles' => '/profiles/',
            'projects' => '/projects/',
            'account' => '/account/',
            default => '/profiles/'
        };

        $result = spin(
            callback: function () use ($apiKey, $baseUrl, $endpoint) {
                $response = Http::withToken($apiKey)
                    ->timeout(30)
                    ->get("{$baseUrl}{$endpoint}");
                return [
                    'status' => $response->status(),
                    'body' => $response->json(),
                    'headers' => $response->headers(),
                ];
            },
            message: "Testing {$endpoint}..."
        );

        $this->line('');
        $this->line('<fg=cyan>═══ API Response ═══</>');
        $this->line("<fg=gray>Status:</> {$result['status']}");
        $this->line("<fg=gray>Endpoint:</> {$baseUrl}{$endpoint}");
        $this->line('');
        $this->line('<fg=yellow>Response Body (truncated):</>');
        $this->line(substr(json_encode($result['body'], JSON_PRETTY_PRINT), 0, 1000));
        
        if (strlen(json_encode($result['body'])) > 1000) {
            note('... response truncated for display');
        }
    }

    private function getImagenApiKey(): string
    {
        return config('services.imagen.api_key') ?? env('IMAGEN_AI_API_KEY', '');
    }

    private function getImagenBaseUrl(): string
    {
        return config('services.imagen.base_url') ?? env('IMAGEN_API_BASE_URL', 'https://api-beta.imagen-ai.com/v1');
    }

    private function getOutputFolders(): array
    {
        $outputDir = storage_path('flambient');
        
        if (!is_dir($outputDir)) {
            return [];
        }

        return collect(File::directories($outputDir))
            ->mapWithKeys(fn($p) => [basename($p) => basename($p) . ' (' . count(File::files($p . '/flambient')) . ' images)'])
            ->toArray();
    }

    // ═══════════════════════════════════════════════════════════
    // DATABASE MENU
    // ═══════════════════════════════════════════════════════════

    private function databaseMenu(): void
    {
        $choice = select(
            label: '📊 Database & Jobs',
            options: [
                'stats' => '📈 Database Statistics',
                'batches' => '📦 View Batches',
                'images' => '🖼️  View Images',
                'stacks' => '📚 View Exposure Stacks',
                'jobs' => '⚙️  View Laravel Jobs Queue',
                'query' => '🔍 Run Custom Query (SELECT only)',
                'tables' => '📋 List All Tables',
                'browse' => '📂 Open Database File',
                'back' => '← Back to Main Menu',
            ],
        );

        match ($choice) {
            'stats' => $this->showDatabaseStats(),
            'batches' => $this->viewBatches(),
            'images' => $this->viewImages(),
            'stacks' => $this->viewStacks(),
            'jobs' => $this->viewJobs(),
            'query' => $this->runRawQuery(),
            'tables' => $this->listDatabaseTables(),
            'browse' => $this->openDatabaseFile(),
            'back' => null,
        };
    }

    private function showDatabaseStats(): void
    {
        $stats = [
            ['Table', 'Records', 'Status'],
            ['Batches', Batch::count(), '✓'],
            ['Images', Image::count(), '✓'],
            ['Exposure Stacks', ExposureStack::count(), '✓'],
        ];

        // Add jobs if table exists
        try {
            $jobCount = DB::table('jobs')->count();
            $stats[] = ['Jobs (Pending)', $jobCount, '✓'];
        } catch (\Exception $e) {
            $stats[] = ['Jobs', 'N/A', '✗'];
        }

        // Add failed jobs if table exists
        try {
            $failedCount = DB::table('failed_jobs')->count();
            $stats[] = ['Failed Jobs', $failedCount, $failedCount > 0 ? '⚠️' : '✓'];
        } catch (\Exception $e) {
            // Table doesn't exist
        }

        table(['Table', 'Records', 'Status'], array_slice($stats, 1));
        
        // Show database file info
        $dbPath = database_path('database.sqlite');
        if (file_exists($dbPath)) {
            $this->newLine();
            note('Database: ' . $dbPath);
            note('Size: ' . $this->formatBytes(filesize($dbPath)));
            note('Modified: ' . date('Y-m-d H:i:s', filemtime($dbPath)));
        }
    }

    private function viewBatches(): void
    {
        $batches = Batch::latest()->take(15)->get();

        if ($batches->isEmpty()) {
            warning('No batches found.');
            return;
        }

        $data = $batches->map(function ($batch) {
            $statusIcon = match($batch->status) {
                'completed' => '✅',
                'processing' => '⏳',
                'failed' => '❌',
                'pending' => '🕐',
                'classified' => '🏷️',
                default => '❓'
            };

            return [
                'ID' => substr($batch->id, 0, 8) . '...',
                'Status' => $statusIcon . ' ' . $batch->status,
                'Images' => $batch->image_count,
                'Size' => $this->formatBytes($batch->total_size),
                'Created' => $batch->created_at->diffForHumans(),
            ];
        })->toArray();

        table(['ID', 'Status', 'Images', 'Size', 'Created'], $data);
    }

    private function viewImages(): void
    {
        $limit = text(
            label: 'How many images to show?',
            default: '20',
            validate: fn($v) => is_numeric($v) ? null : 'Must be a number'
        );

        $images = Image::with('metadata')->latest()->take((int)$limit)->get();

        if ($images->isEmpty()) {
            warning('No images found.');
            return;
        }

        $data = $images->map(function ($image) {
            $tag = $image->metadata?->tag ?? '-';
            $tagIcon = match($tag) {
                'flash' => '⚡',
                'ambient' => '☀️',
                default => '❓'
            };

            return [
                'Filename' => substr($image->filename, 0, 30),
                'Flash' => $image->metadata?->flash_status ?? 'N/A',
                'ISO' => $image->metadata?->iso ?? 'N/A',
                'Tag' => $tagIcon . ' ' . $tag,
                'Size' => $this->formatBytes($image->file_size),
            ];
        })->toArray();

        table(['Filename', 'Flash', 'ISO', 'Tag', 'Size'], $data);
    }

    private function viewStacks(): void
    {
        $stacks = ExposureStack::with('batch')->latest()->take(15)->get();

        if ($stacks->isEmpty()) {
            warning('No exposure stacks found.');
            return;
        }

        $data = $stacks->map(function ($stack) {
            return [
                'ID' => $stack->id,
                'Batch' => substr($stack->batch_id, 0, 8) . '...',
                'Flash' => $stack->flash_count ?? 0,
                'Ambient' => $stack->ambient_count ?? 0,
                'Status' => $stack->status ?? 'pending',
            ];
        })->toArray();

        table(['ID', 'Batch', 'Flash', 'Ambient', 'Status'], $data);
    }

    private function viewJobs(): void
    {
        try {
            $jobs = DB::table('jobs')->latest()->take(20)->get();

            if ($jobs->isEmpty()) {
                warning('No pending jobs in queue.');
                
                // Check failed jobs
                try {
                    $failedCount = DB::table('failed_jobs')->count();
                    if ($failedCount > 0) {
                        note("There are {$failedCount} failed jobs. Run 'php artisan queue:failed' to view.");
                    }
                } catch (\Exception $e) {}
                
                return;
            }

            $data = $jobs->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return [
                    'ID' => $job->id,
                    'Queue' => $job->queue,
                    'Job' => class_basename($payload['displayName'] ?? 'Unknown'),
                    'Attempts' => $job->attempts,
                    'Created' => date('Y-m-d H:i', $job->created_at),
                ];
            })->toArray();

            table(['ID', 'Queue', 'Job', 'Attempts', 'Created'], $data);
        } catch (\Exception $e) {
            warning('Jobs table not found. Run: php artisan queue:table && php artisan migrate');
        }
    }

    private function runRawQuery(): void
    {
        $query = text(
            label: 'Enter SQL Query (SELECT only)',
            placeholder: 'SELECT * FROM flambient_batches LIMIT 5',
            required: true,
        );

        if (!str_starts_with(strtoupper(trim($query)), 'SELECT')) {
            error('Only SELECT queries are allowed for safety.');
            return;
        }

        try {
            $results = spin(
                callback: fn() => DB::select($query),
                message: 'Executing query...'
            );
            
            if (empty($results)) {
                warning('Query returned no results.');
                return;
            }

            $headers = array_keys((array) $results[0]);
            $data = array_map(function($row) {
                return array_map(function($value) {
                    if (is_string($value) && strlen($value) > 40) {
                        return substr($value, 0, 37) . '...';
                    }
                    return $value;
                }, array_values((array) $row));
            }, $results);

            table($headers, $data);
            note('Returned ' . count($results) . ' rows');
        } catch (\Exception $e) {
            error('Query error: ' . $e->getMessage());
        }
    }

    private function listDatabaseTables(): void
    {
        try {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
            
            $data = collect($tables)->map(function ($table) {
                $count = DB::table($table->name)->count();
                return [
                    'Table' => $table->name,
                    'Records' => $count,
                ];
            })->toArray();

            table(['Table', 'Records'], $data);
        } catch (\Exception $e) {
            error('Error listing tables: ' . $e->getMessage());
        }
    }

    private function openDatabaseFile(): void
    {
        $dbPath = database_path('database.sqlite');
        
        if (!file_exists($dbPath)) {
            warning('SQLite database file not found.');
            return;
        }

        $choice = select(
            label: 'How would you like to open the database?',
            options: [
                'finder' => 'Show in Finder',
                'tableplus' => 'Open in TablePlus',
                'sqlite' => 'Open in sqlite3 CLI',
                'back' => '← Back'
            ]
        );

        match ($choice) {
            'finder' => Process::run("open -R '{$dbPath}'"),
            'tableplus' => Process::run("open -a TablePlus '{$dbPath}'"),
            'sqlite' => $this->runSqliteCli($dbPath),
            'back' => null,
        };
    }

    private function runSqliteCli(string $dbPath): void
    {
        info("Opening SQLite CLI. Type '.quit' to exit.");
        Process::tty()->run("sqlite3 '{$dbPath}'");
    }

    // ═══════════════════════════════════════════════════════════
    // CAMERA TETHERING MENU
    // ═══════════════════════════════════════════════════════════

    private function tetheringMenu(): void
    {
        $choice = select(
            label: '📷 Camera Tethering & Import',
            options: [
                'live' => '📷 Live Capture (photos-capture-live)',
                'sync' => '🔄 Sync from Photos.app (photos-capture-sync)',
                'debug' => '🐛 Debug Tethering (photos-tether-debug)',
                'status' => '❓ Check Photos.app Status',
                'import' => '📥 Import from Folder',
                'help' => '📖 Tethering Help & Requirements',
                'back' => '← Back to Main Menu',
            ],
        );

        match ($choice) {
            'live' => $this->runTetherScript('photos-capture-live'),
            'sync' => $this->runTetherScript('photos-capture-sync'),
            'debug' => $this->runTetherScript('photos-tether-debug'),
            'status' => $this->checkPhotosStatus(),
            'import' => $this->importFromFolder(),
            'help' => $this->showTetheringHelp(),
            'back' => null,
        };
    }

    private function runTetherScript(string $script): void
    {
        $scriptPath = resource_path("exe/{$script}");

        if (!file_exists($scriptPath)) {
            error("Script not found: {$scriptPath}");
            $this->line('');
            note('Expected scripts in resources/exe/:');
            note('  - photos-capture-live');
            note('  - photos-capture-sync');
            note('  - photos-tether-debug');
            $this->line('');
            
            if (confirm('Create placeholder script?')) {
                $this->createPlaceholderScript($scriptPath, $script);
            }
            return;
        }

        if (!is_executable($scriptPath)) {
            warning("Script is not executable. Running: chmod +x {$script}");
            Process::run("chmod +x '{$scriptPath}'");
        }

        $this->line('');
        info("Running: {$script}");
        $this->line('<fg=gray>─────────────────────────────────────────────</>');
        
        $result = Process::timeout(300)->run($scriptPath);
        
        $this->line($result->output());
        
        if ($result->errorOutput()) {
            $this->line('<fg=red>' . $result->errorOutput() . '</>');
        }
        
        $this->line('<fg=gray>─────────────────────────────────────────────</>');
        
        if ($result->failed()) {
            error('Script failed with exit code: ' . $result->exitCode());
        } else {
            info('Script completed successfully.');
        }
    }

    private function createPlaceholderScript(string $path, string $name): void
    {
        $content = <<<BASH
#!/bin/bash
# {$name}
# Auto-generated placeholder - replace with actual implementation
# Part of D-MEC Image Processor

echo "📷 D-MEC: {$name}"
echo "========================"
echo ""
echo "This is a placeholder script."
echo "Replace this file with your actual tethering implementation."
echo ""
echo "For tethering support, see:"
echo "  - https://support.apple.com/guide/photos"
echo "  - Camera-specific tethering software documentation"
BASH;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        Process::run("chmod +x '{$path}'");
        
        info("Created placeholder: {$path}");
    }

    private function checkPhotosStatus(): void
    {
        info('Checking system status...');
        $this->line('');

        // Check Photos.app
        $photosResult = Process::run('pgrep -x Photos');
        if ($photosResult->successful()) {
            $this->line('<fg=green>✓</> Photos.app is running (PID: ' . trim($photosResult->output()) . ')');
        } else {
            $this->line('<fg=yellow>○</> Photos.app is not running');
        }

        // Check for connected cameras via system_profiler
        $cameraResult = Process::run('system_profiler SPUSBDataType 2>/dev/null | grep -i camera');
        if ($cameraResult->successful() && trim($cameraResult->output())) {
            $this->line('<fg=green>✓</> Camera detected via USB');
        } else {
            $this->line('<fg=yellow>○</> No camera detected via USB');
        }

        // Check Image Capture
        $imageCaptureResult = Process::run('pgrep -x "Image Capture"');
        if ($imageCaptureResult->successful()) {
            $this->line('<fg=green>✓</> Image Capture is running');
        } else {
            $this->line('<fg=gray>○</> Image Capture not running');
        }

        $this->line('');
        
        if (!$photosResult->successful()) {
            if (confirm('Open Photos.app?')) {
                Process::run('open -a Photos');
                info('Photos.app launched');
            }
        }
    }

    private function importFromFolder(): void
    {
        $path = text(
            label: 'Enter folder path to import from',
            placeholder: '/Users/you/Pictures/CameraImport',
            required: true,
            validate: fn($v) => is_dir($v) ? null : 'Directory does not exist'
        );

        $files = glob("{$path}/*.{jpg,jpeg,JPG,JPEG}", GLOB_BRACE);
        
        if (empty($files)) {
            warning('No JPEG files found in that directory.');
            return;
        }

        info('Found ' . count($files) . ' JPEG files.');
        
        if (confirm('Import these files to a new batch?')) {
            // This would integrate with your upload controller
            warning('Batch import not yet integrated with CLI.');
            note('Use the web interface at: ' . config('app.url') . '/upload');
        }
    }

    private function showTetheringHelp(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  📷 CAMERA TETHERING & IMPORT GUIDE</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=white>Available Scripts (in resources/exe/):</>');
        $this->line('');
        $this->line('  <fg=yellow>photos-capture-live</>');
        $this->line('    Live capture from tethered camera via Photos.app');
        $this->line('    Triggers camera shutter and captures directly');
        $this->line('');
        $this->line('  <fg=yellow>photos-capture-sync</>');
        $this->line('    Syncs photos from Photos.app library to local folder');
        $this->line('    Useful for batch importing captured images');
        $this->line('');
        $this->line('  <fg=yellow>photos-tether-debug</>');
        $this->line('    Diagnostic mode for troubleshooting tethering issues');
        $this->line('    Shows connection status and camera info');
        $this->line('');
        $this->line('<fg=white>Requirements:</>');
        $this->line('  • macOS with Photos.app');
        $this->line('  • Camera connected via USB (PTP/MTP) or WiFi');
        $this->line('  • Camera tethering enabled in camera settings');
        $this->line('  • Scripts must be executable (chmod +x)');
        $this->line('');
        $this->line('<fg=white>Supported Cameras:</>');
        $this->line('  Most cameras that support macOS Image Capture will work.');
        $this->line('  For professional tethering, consider:');
        $this->line('  • Capture One Pro');
        $this->line('  • Adobe Lightroom');
        $this->line('  • Camera manufacturer software (Canon EOS Utility, Nikon NX, etc.)');
        $this->line('');
        $this->line('<fg=white>Alternative: Direct Import</>');
        $this->line('  Use "Import from Folder" to batch import from any directory.');
        $this->line('');
    }

    // ═══════════════════════════════════════════════════════════
    // MANUAL & DOCUMENTATION MENU
    // ═══════════════════════════════════════════════════════════

    private function manualMenu(): void
    {
        $choice = select(
            label: '📖 Manual & Documentation',
            options: [
                'overview' => '🏠 Application Overview',
                'quickstart' => '🚀 Quick Start Guide',
                'dmec' => '💡 D-MEC Theory & Background',
                'workflow' => '📋 Processing Workflow Guide',
                'imagemagick' => '🪄 ImageMagick Reference',
                'imagen' => '☁️  Imagen AI Integration Guide',
                'troubleshoot' => '🔧 Troubleshooting Guide',
                'faq' => '❓ Frequently Asked Questions',
                'changelog' => '📝 Version History',
                'back' => '← Back to Main Menu',
            ],
        );

        match ($choice) {
            'overview' => $this->showOverview(),
            'quickstart' => $this->showQuickStart(),
            'dmec' => $this->showDMECTheory(),
            'workflow' => $this->showWorkflowGuide(),
            'imagemagick' => $this->showImageMagickGuide(),
            'imagen' => $this->showImagenGuide(),
            'troubleshoot' => $this->showTroubleshooting(),
            'faq' => $this->showFAQ(),
            'changelog' => $this->showChangelog(),
            'back' => null,
        };
    }

    private function showOverview(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  D-MEC IMAGE PROCESSOR - APPLICATION OVERVIEW</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=white>The D-MEC Image Processor is a Laravel-based workflow automation</>');
        $this->line('<fg=white>system for real estate photography, implementing the Dissimilar</>');
        $this->line('<fg=white>Multiple Exposure Composition (D-MEC) technique.</>');
        $this->line('');
        $this->line('<fg=green>Key Features:</>');
        $this->line('  • <fg=yellow>EXIF-based</> automatic exposure classification');
        $this->line('  • <fg=yellow>ImageMagick</> script generation for exposure blending');
        $this->line('  • <fg=yellow>Imagen AI</> integration for AI-powered color grading');
        $this->line('  • <fg=yellow>SQLite</> database for job tracking and resume');
        $this->line('  • <fg=yellow>Camera tethering</> support via macOS Photos.app');
        $this->line('  • <fg=yellow>Plugin architecture</> for multiple processing techniques');
        $this->line('');
        $this->line('<fg=green>Architecture:</>');
        $this->line('  • Modular script generators (Flambient, HDR, Focus Stack, etc.)');
        $this->line('  • Service-based architecture with clear separation');
        $this->line('  • Laravel Prompts for beautiful CLI interaction');
        $this->line('  • Full state management with resume capability');
        $this->line('');
        $this->line('<fg=green>Technical Stack:</>');
        $this->line('  • PHP 8.2+ with Laravel 11');
        $this->line('  • SQLite for portable database');
        $this->line('  • ImageMagick 7+ for image processing');
        $this->line('  • ExifTool for metadata extraction');
        $this->line('');
        
        if (confirm('View Quick Start guide?')) {
            $this->showQuickStart();
        }
    }

    private function showQuickStart(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  🚀 QUICK START GUIDE</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=white>Get started in 5 steps:</>');
        $this->line('');
        $this->line('<fg=green>1. Configure Environment</>');
        $this->line('   Copy .env.example to .env and set:');
        $this->line('   <fg=gray>IMAGEN_AI_API_KEY=your_api_key</>');
        $this->line('   <fg=gray>IMAGEN_PROFILE_KEY=your_profile</>');
        $this->line('');
        $this->line('<fg=green>2. Run Migrations</>');
        $this->line('   <fg=gray>php artisan migrate</>');
        $this->line('');
        $this->line('<fg=green>3. Upload Images</>');
        $this->line('   Either via web interface or direct file copy');
        $this->line('   <fg=gray>php artisan serve  # Then visit /upload</>');
        $this->line('');
        $this->line('<fg=green>4. Process with Flambient</>');
        $this->line('   <fg=gray>php artisan flambient:process --local</>');
        $this->line('');
        $this->line('<fg=green>5. Upload to Imagen AI (optional)</>');
        $this->line('   <fg=gray>php artisan flambient:process  # Full workflow</>');
        $this->line('');
        $this->line('<fg=white>For interactive mode, just run:</> <fg=yellow>php artisan home</>');
        $this->line('');
    }

    private function showDMECTheory(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  💡 DISSIMILAR MULTIPLE EXPOSURE COMPOSITION (D-MEC)</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=white>D-MEC is a photography technique that combines multiple exposures</>');
        $this->line('<fg=white>taken from a fixed camera position to cherry-pick the best</>');
        $this->line('<fg=white>attributes from each shot.</>');
        $this->line('');
        $this->line('<fg=green>Core Principle:</> "Dissimilar" exposures differ intentionally:');
        $this->line('  • Different lighting (flash vs ambient)');
        $this->line('  • Different exposure values');
        $this->line('  • Different focus points (for focus stacking)');
        $this->line('');
        $this->line('<fg=yellow>━━━ FLAMBIENT TECHNIQUE ━━━</>');
        $this->line('');
        $this->line('  <fg=cyan>Flash Exposure</> provides:');
        $this->line('    ⚡ Clean, noise-free shadows');
        $this->line('    ⚡ Accurate interior color');
        $this->line('    ⚡ Sharp detail in dark areas');
        $this->line('');
        $this->line('  <fg=cyan>Ambient Exposure</> provides:');
        $this->line('    ☀️  Natural window views');
        $this->line('    ☀️  Realistic lighting atmosphere');
        $this->line('    ☀️  Proper exterior exposure');
        $this->line('');
        $this->line('  <fg=cyan>Blending Process</>:');
        $this->line('    1. Use flash as base layer (interior)');
        $this->line('    2. Extract bright regions from ambient (windows)');
        $this->line('    3. Mask and blend for balanced exposure');
        $this->line('    4. Apply luminosity refinements');
        $this->line('');
        $this->line('<fg=yellow>━━━ OTHER D-MEC TECHNIQUES ━━━</>');
        $this->line('');
        $this->line('  <fg=cyan>HDR Merge</>     - Bracket exposures for dynamic range');
        $this->line('  <fg=cyan>Focus Stack</>   - Multiple focus points for depth of field');
        $this->line('  <fg=cyan>Time Blend</>    - Day/dusk/night for twilight shots');
        $this->line('');
    }

    private function showWorkflowGuide(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  📋 PROCESSING WORKFLOW GUIDE</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=white>Complete workflow from camera to final delivery:</>');
        $this->line('');
        $this->line('<fg=green>PHASE 1: CAPTURE</>');
        $this->line('  ┌─ Camera Setup (tripod, composition)');
        $this->line('  ├─ Flash Exposure (strobe/speedlight)');
        $this->line('  ├─ Ambient Exposure (natural light)');
        $this->line('  └─ Optional: Additional brackets');
        $this->line('');
        $this->line('<fg=green>PHASE 2: IMPORT</>');
        $this->line('  ┌─ Transfer files (tether or card import)');
        $this->line('  ├─ Upload to D-MEC (web or CLI)');
        $this->line('  └─ EXIF extraction & classification');
        $this->line('');
        $this->line('<fg=green>PHASE 3: CLASSIFY</>');
        $this->line('  ┌─ Auto-tag based on flash metadata');
        $this->line('  ├─ Review and correct tags');
        $this->line('  └─ Group into exposure stacks');
        $this->line('');
        $this->line('<fg=green>PHASE 4: PROCESS (Local)</>');
        $this->line('  ┌─ Generate ImageMagick scripts');
        $this->line('  ├─ Execute blending algorithms');
        $this->line('  └─ Output blended JPEGs');
        $this->line('');
        $this->line('<fg=green>PHASE 5: ENHANCE (Cloud - Optional)</>');
        $this->line('  ┌─ Upload to Imagen AI');
        $this->line('  ├─ AI color grading & corrections');
        $this->line('  ├─ Window pull & exposure balance');
        $this->line('  └─ Download enhanced images');
        $this->line('');
        $this->line('<fg=green>PHASE 6: DELIVER</>');
        $this->line('  ┌─ Review final images');
        $this->line('  ├─ Organize output folders');
        $this->line('  └─ Export/upload to client');
        $this->line('');
    }

    private function showImageMagickGuide(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  🪄 IMAGEMAGICK REFERENCE</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=green>Installation:</>');
        $this->line('  <fg=gray>brew install imagemagick</>');
        $this->line('  <fg=gray>magick --version  # Verify</>');
        $this->line('');
        $this->line('<fg=green>Key Operations Used in D-MEC:</>');
        $this->line('');
        $this->line('  <fg=yellow>-level</> - Adjust input/output levels');
        $this->line('    Example: -level 40%,140%');
        $this->line('    Expands contrast, clips shadows/highlights');
        $this->line('');
        $this->line('  <fg=yellow>-gamma</> - Gamma correction');
        $this->line('    Example: -gamma 1.2');
        $this->line('    Values >1 brighten, <1 darken midtones');
        $this->line('');
        $this->line('  <fg=yellow>-compose</> - Blending modes');
        $this->line('    • Darken  - Keep darker pixels');
        $this->line('    • Lighten - Keep lighter pixels');
        $this->line('    • Over    - Standard layer composite');
        $this->line('    • Multiply - Darken by multiplying');
        $this->line('');
        $this->line('  <fg=yellow>-morphology</> - Morphological operations');
        $this->line('    Used for mask refinement and edge feathering');
        $this->line('');
        $this->line('  <fg=yellow>-blur</> - Gaussian blur');
        $this->line('    Example: -blur 0x5');
        $this->line('    Used for mask edge softening');
        $this->line('');
        $this->line('<fg=green>Script Format (.mgk):</>');
        $this->line('  ImageMagick scripts are plain text files containing');
        $this->line('  a sequence of commands, one per line.');
        $this->line('');
        $this->line('  <fg=gray>Execute with: magick -script filename.mgk</>');
        $this->line('');
        $this->line('<fg=green>Common Issues:</>');
        $this->line('  • "command not found" - ImageMagick not in PATH');
        $this->line('  • Memory errors - Add: -limit memory 2GB');
        $this->line('  • Slow processing - Use -quality 92 not 100');
        $this->line('');
    }

    private function showImagenGuide(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  ☁️  IMAGEN AI INTEGRATION GUIDE</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=green>What is Imagen AI?</>');
        $this->line('  Cloud-based AI photo editing service that learns your');
        $this->line('  editing style and applies it automatically to new images.');
        $this->line('');
        $this->line('<fg=green>API Workflow:</>');
        $this->line('  Upload → Edit → Poll → Export → Download');
        $this->line('');
        $this->line('<fg=green>Configuration (.env):</>');
        $this->line('  <fg=gray>IMAGEN_AI_API_KEY=your_api_key</>');
        $this->line('  <fg=gray>IMAGEN_PROFILE_KEY=309406</>');
        $this->line('  <fg=gray>IMAGEN_API_BASE_URL=https://api-beta.imagen-ai.com/v1</>');
        $this->line('  <fg=gray>IMAGEN_TIMEOUT=30</>');
        $this->line('  <fg=gray>IMAGEN_POLL_INTERVAL=30</>');
        $this->line('  <fg=gray>IMAGEN_POLL_MAX_ATTEMPTS=240</>');
        $this->line('');
        $this->line('<fg=green>Available Edit Options:</>');
        $this->line('  • <fg=yellow>crop</> - Auto-crop to improve composition');
        $this->line('  • <fg=yellow>windowPull</> - Balance window exposure');
        $this->line('  • <fg=yellow>perspectiveCorrection</> - Fix vertical lines');
        $this->line('  • <fg=yellow>hdrMerge</> - Merge exposure brackets');
        $this->line('');
        $this->line('<fg=green>Photography Types:</>');
        $this->line('  REAL_ESTATE, WEDDING, PORTRAIT, PRODUCT, LANDSCAPE, EVENT');
        $this->line('');
        $this->line('<fg=green>Profiles:</>');
        $this->line('  Profiles are trained on your editing style. Each profile');
        $this->line('  has a unique key that you specify when starting edits.');
        $this->line('  Use "List Available Profiles" to see your profiles.');
        $this->line('');
    }

    private function showTroubleshooting(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  🔧 TROUBLESHOOTING GUIDE</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=red>━━━ EXIF ISSUES ━━━</>');
        $this->line('');
        $this->line('<fg=yellow>Problem:</> EXIF extraction fails');
        $this->line('<fg=green>Solutions:</>');
        $this->line('  1. Install exiftool: <fg=gray>brew install exiftool</>');
        $this->line('  2. Verify: <fg=gray>exiftool -ver</>');
        $this->line('  3. Check file permissions');
        $this->line('  4. Ensure files are valid JPEGs');
        $this->line('');
        $this->line('<fg=yellow>Problem:</> Flash detection fails');
        $this->line('<fg=green>Solutions:</>');
        $this->line('  1. Check if camera records flash in EXIF');
        $this->line('  2. Use manual tagging in the classify interface');
        $this->line('  3. Verify flash EXIF field: <fg=gray>exiftool -Flash image.jpg</>');
        $this->line('');
        $this->line('<fg=red>━━━ IMAGEMAGICK ISSUES ━━━</>');
        $this->line('');
        $this->line('<fg=yellow>Problem:</> "magick: command not found"');
        $this->line('<fg=green>Solutions:</>');
        $this->line('  1. Install: <fg=gray>brew install imagemagick</>');
        $this->line('  2. Verify PATH includes Homebrew bin');
        $this->line('  3. Try full path: <fg=gray>/opt/homebrew/bin/magick</>');
        $this->line('');
        $this->line('<fg=yellow>Problem:</> Scripts produce black/white images');
        $this->line('<fg=green>Solutions:</>');
        $this->line('  1. Check input file paths in .mgk script');
        $this->line('  2. Verify level adjustments aren\'t clipping');
        $this->line('  3. Test with simpler compose operations');
        $this->line('');
        $this->line('<fg=red>━━━ IMAGEN API ISSUES ━━━</>');
        $this->line('');
        $this->line('<fg=yellow>Problem:</> 401 Unauthorized');
        $this->line('<fg=green>Solutions:</>');
        $this->line('  1. Verify API key in .env');
        $this->line('  2. Check key hasn\'t expired');
        $this->line('  3. Ensure no extra spaces in key');
        $this->line('');
        $this->line('<fg=yellow>Problem:</> 429 Rate Limited');
        $this->line('<fg=green>Solutions:</>');
        $this->line('  1. Wait 60 seconds and retry');
        $this->line('  2. Reduce parallel upload count');
        $this->line('  3. Check your API tier limits');
        $this->line('');
        $this->line('<fg=red>━━━ DATABASE ISSUES ━━━</>');
        $this->line('');
        $this->line('<fg=yellow>Problem:</> Table not found');
        $this->line('<fg=green>Solutions:</>');
        $this->line('  1. Run migrations: <fg=gray>php artisan migrate</>');
        $this->line('  2. Check database.sqlite exists');
        $this->line('  3. Verify DB_CONNECTION=sqlite in .env');
        $this->line('');
    }

    private function showFAQ(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  ❓ FREQUENTLY ASKED QUESTIONS</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=yellow>Q: Do I need Imagen AI to use this?</>');
        $this->line('A: No. Use --local flag for ImageMagick-only processing.');
        $this->line('');
        $this->line('<fg=yellow>Q: What cameras are supported?</>');
        $this->line('A: Any camera that produces JPEGs with EXIF data.');
        $this->line('   Flash detection requires flash EXIF metadata.');
        $this->line('');
        $this->line('<fg=yellow>Q: Can I process RAW files?</>');
        $this->line('A: Currently only JPEG is supported. Convert RAW to JPEG');
        $this->line('   first using Lightroom, Capture One, or dcraw.');
        $this->line('');
        $this->line('<fg=yellow>Q: How do I add new processing techniques?</>');
        $this->line('A: Create a new Generator class implementing');
        $this->line('   ScriptGeneratorInterface. See README for examples.');
        $this->line('');
        $this->line('<fg=yellow>Q: Why are my blended images dark/bright?</>');
        $this->line('A: Adjust ImageMagick level settings in .env:');
        $this->line('   IMAGEMAGICK_LEVEL_LOW and IMAGEMAGICK_LEVEL_HIGH');
        $this->line('');
        $this->line('<fg=yellow>Q: Can I resume a failed Imagen upload?</>');
        $this->line('A: Yes, job state is stored in the database. Use');
        $this->line('   "View Local Job Records" to find the project UUID.');
        $this->line('');
        $this->line('<fg=yellow>Q: Where are processed images saved?</>');
        $this->line('A: In storage/flambient/{project-name}/flambient/');
        $this->line('   Edited images go to /edited/ subfolder.');
        $this->line('');
    }

    private function showChangelog(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  📝 VERSION HISTORY</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');
        $this->line('<fg=green>v2.1.0</> - <fg=gray>"Flazsh Revival"</> - ' . date('Y-m-d'));
        $this->line('  • Enhanced home command with full navigation');
        $this->line('  • Camera tethering integration');
        $this->line('  • Comprehensive documentation pages');
        $this->line('  • Imagen AI job tracking');
        $this->line('  • Database browser improvements');
        $this->line('');
        $this->line('<fg=green>v2.0.0</> - <fg=gray>"Laravel Edition"</>');
        $this->line('  • Complete rewrite in Laravel');
        $this->line('  • Plugin-based script generator architecture');
        $this->line('  • SQLite database for state management');
        $this->line('  • Laravel Prompts for CLI UX');
        $this->line('  • Imagen AI PHP client');
        $this->line('');
        $this->line('<fg=green>v1.x</> - <fg=gray>"Fla.zsh Era"</>');
        $this->line('  • Original ZSH shell scripts');
        $this->line('  • AWK-based script generation');
        $this->line('  • Manual curl-based API calls');
        $this->line('');
    }

    // ═══════════════════════════════════════════════════════════
    // DEBUG MENU
    // ═══════════════════════════════════════════════════════════

    private function debugMenu(): void
    {
        $choice = select(
            label: '🔧 Debug & Diagnostics',
            options: [
                'health' => '❤️  System Health Check',
                'env' => '📋 Environment Variables',
                'api' => '🌐 Test API Connections',
                'deps' => '📦 Check Dependencies',
                'logs' => '📜 View Recent Logs',
                'clear' => '🧹 Clear Caches',
                'routes' => '🛤️  List Artisan Commands',
                'phpinfo' => '🐘 PHP Info',
                'back' => '← Back to Main Menu',
            ],
        );

        match ($choice) {
            'health' => $this->runHealthCheck(),
            'env' => $this->showEnvironment(),
            'api' => $this->testAllConnections(),
            'deps' => $this->checkDependencies(),
            'logs' => $this->viewRecentLogs(),
            'clear' => $this->clearCaches(),
            'routes' => $this->listArtisanCommands(),
            'phpinfo' => $this->showPhpInfo(),
            'back' => null,
        };
    }

    private function runHealthCheck(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  ❤️  SYSTEM HEALTH CHECK</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');

        $checks = [];

        // PHP Version
        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.2.0', '>=');
        $checks[] = ['PHP Version', $phpVersion, $phpOk];

        // Laravel Version
        $laravelVersion = app()->version();
        $checks[] = ['Laravel Version', $laravelVersion, true];

        // Database Connection
        try {
            DB::connection()->getPdo();
            $checks[] = ['Database Connection', 'Connected', true];
        } catch (\Exception $e) {
            $checks[] = ['Database Connection', 'Failed', false];
        }

        // ExifTool
        $exifResult = Process::run('which exiftool');
        $checks[] = ['ExifTool', $exifResult->successful() ? 'Installed' : 'Missing', $exifResult->successful()];

        // ImageMagick
        $magickResult = Process::run('which magick');
        $checks[] = ['ImageMagick', $magickResult->successful() ? 'Installed' : 'Missing', $magickResult->successful()];

        // Imagen API Key
        $imagenKey = !empty(env('IMAGEN_AI_API_KEY'));
        $checks[] = ['Imagen API Key', $imagenKey ? 'Configured' : 'Not Set', $imagenKey];

        // Storage Writable
        $storageWritable = is_writable(storage_path());
        $checks[] = ['Storage Writable', $storageWritable ? 'Yes' : 'No', $storageWritable];

        // Display results
        foreach ($checks as [$name, $value, $ok]) {
            $icon = $ok ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line("  {$icon} <fg=white>{$name}:</> {$value}");
        }

        $this->line('');
        
        $allOk = collect($checks)->every(fn($c) => $c[2]);
        if ($allOk) {
            info('All systems operational! ✨');
        } else {
            warning('Some issues detected. Review the items marked with ✗');
        }
    }

    private function showEnvironment(): void
    {
        $env = [
            ['Category', 'Key', 'Value'],
            ['App', 'APP_ENV', env('APP_ENV', 'production')],
            ['App', 'APP_DEBUG', env('APP_DEBUG') ? 'true' : 'false'],
            ['App', 'APP_URL', env('APP_URL', 'http://localhost')],
            ['Database', 'DB_CONNECTION', env('DB_CONNECTION', 'sqlite')],
            ['Database', 'DB_DATABASE', basename(env('DB_DATABASE', 'database.sqlite'))],
            ['Imagen', 'API Key', env('IMAGEN_AI_API_KEY') ? '✓ Set (' . strlen(env('IMAGEN_AI_API_KEY')) . ' chars)' : '✗ Not Set'],
            ['Imagen', 'Profile Key', env('IMAGEN_PROFILE_KEY', '309406')],
            ['Imagen', 'Base URL', env('IMAGEN_API_BASE_URL', 'default')],
            ['ImageMagick', 'Binary', env('IMAGEMAGICK_BINARY', 'magick')],
            ['ImageMagick', 'Level Low', env('IMAGEMAGICK_LEVEL_LOW', '40%')],
            ['ImageMagick', 'Level High', env('IMAGEMAGICK_LEVEL_HIGH', '140%')],
            ['System', 'PHP Version', PHP_VERSION],
            ['System', 'Laravel', app()->version()],
            ['System', 'Memory Limit', ini_get('memory_limit')],
        ];

        table(['Category', 'Key', 'Value'], array_slice($env, 1));
    }

    private function testAllConnections(): void
    {
        $this->line('');
        info('Testing all connections...');
        $this->line('');

        // Database
        $this->line('<fg=yellow>Database:</> ');
        try {
            DB::connection()->getPdo();
            $this->line('  <fg=green>✓</> SQLite connected');
            $this->line('  <fg=gray>  Tables: ' . count(DB::select("SELECT name FROM sqlite_master WHERE type='table'")) . '</>');
        } catch (\Exception $e) {
            $this->line('  <fg=red>✗</> Failed: ' . $e->getMessage());
        }

        // Imagen API
        $this->line('');
        $this->line('<fg=yellow>Imagen API:</> ');
        if (!$this->getImagenStatus()) {
            $this->line('  <fg=red>✗</> API key not configured');
        } else {
            try {
                $result = Http::withToken($this->getImagenApiKey())
                    ->timeout(10)
                    ->get($this->getImagenBaseUrl() . '/profiles/');
                
                if ($result->successful()) {
                    $this->line('  <fg=green>✓</> API reachable (status ' . $result->status() . ')');
                } else {
                    $this->line('  <fg=red>✗</> API error: ' . $result->status());
                }
            } catch (\Exception $e) {
                $this->line('  <fg=red>✗</> Connection failed: ' . $e->getMessage());
            }
        }

        // External tools
        $this->line('');
        $this->line('<fg=yellow>External Tools:</> ');
        
        $tools = [
            'exiftool' => 'ExifTool',
            'magick' => 'ImageMagick',
            'curl' => 'cURL',
        ];

        foreach ($tools as $cmd => $name) {
            $result = Process::run("which {$cmd}");
            if ($result->successful()) {
                $this->line("  <fg=green>✓</> {$name}: " . trim($result->output()));
            } else {
                $this->line("  <fg=red>✗</> {$name}: Not found in PATH");
            }
        }

        $this->line('');
    }

    private function checkDependencies(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  📦 DEPENDENCY CHECK</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');

        // Required CLI tools
        $this->line('<fg=white>Required CLI Tools:</>');
        $required = [
            ['exiftool', 'brew install exiftool'],
            ['magick', 'brew install imagemagick'],
        ];

        foreach ($required as [$cmd, $install]) {
            $result = Process::run("which {$cmd}");
            if ($result->successful()) {
                $versionResult = Process::run("{$cmd} -ver 2>/dev/null || {$cmd} --version 2>/dev/null | head -1");
                $version = trim($versionResult->output()) ?: 'version unknown';
                $this->line("  <fg=green>✓</> {$cmd}: {$version}");
            } else {
                $this->line("  <fg=red>✗</> {$cmd}: Not installed");
                $this->line("      <fg=gray>Install: {$install}</>");
            }
        }

        // PHP Extensions
        $this->line('');
        $this->line('<fg=white>Required PHP Extensions:</>');
        $extensions = ['sqlite3', 'json', 'mbstring', 'curl', 'openssl'];

        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                $this->line("  <fg=green>✓</> {$ext}");
            } else {
                $this->line("  <fg=red>✗</> {$ext}: Not loaded");
            }
        }

        // Composer packages
        $this->line('');
        $this->line('<fg=white>Key Composer Packages:</>');
        $composerLock = base_path('composer.lock');
        if (file_exists($composerLock)) {
            $lock = json_decode(file_get_contents($composerLock), true);
            $packages = collect($lock['packages'] ?? [])->keyBy('name');
            
            $keyPackages = ['laravel/framework', 'laravel/prompts', 'guzzlehttp/guzzle'];
            foreach ($keyPackages as $pkg) {
                if ($packages->has($pkg)) {
                    $this->line("  <fg=green>✓</> {$pkg}: " . $packages->get($pkg)['version']);
                } else {
                    $this->line("  <fg=yellow>○</> {$pkg}: Not installed");
                }
            }
        }

        $this->line('');
    }

    private function viewRecentLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (!file_exists($logPath)) {
            warning('No log file found at: ' . $logPath);
            return;
        }

        $lines = text(
            label: 'How many lines to show?',
            default: '50',
            validate: fn($v) => is_numeric($v) ? null : 'Must be a number'
        );

        $filter = select(
            label: 'Filter logs?',
            options: [
                'all' => 'All entries',
                'error' => 'Errors only',
                'warning' => 'Warnings and errors',
                'info' => 'Info, warnings, and errors',
            ],
            default: 'all'
        );

        $grepFilter = match($filter) {
            'error' => ' | grep -i "error\|exception\|fatal"',
            'warning' => ' | grep -i "error\|exception\|warning\|fatal"',
            'info' => ' | grep -i "error\|exception\|warning\|info\|fatal"',
            default => ''
        };

        $result = Process::run("tail -n {$lines} '{$logPath}'{$grepFilter}");
        
        $this->line('');
        $this->line('<fg=gray>─────────────────────────────────────────────</>');
        $this->line($result->output() ?: '<fg=gray>No matching entries</>');
        $this->line('<fg=gray>─────────────────────────────────────────────</>');
        $this->line('');
        
        note("Log file: {$logPath}");
        note("Size: " . $this->formatBytes(filesize($logPath)));
    }

    private function clearCaches(): void
    {
        if (!confirm('Clear all application caches?')) {
            return;
        }

        $commands = [
            'cache:clear' => 'Application cache',
            'config:clear' => 'Configuration cache',
            'view:clear' => 'Compiled views',
            'route:clear' => 'Route cache',
            'event:clear' => 'Event cache',
        ];

        foreach ($commands as $command => $description) {
            try {
                spin(
                    callback: fn() => $this->callSilently($command),
                    message: "Clearing {$description}..."
                );
                $this->line("  <fg=green>✓</> {$description}");
            } catch (\Exception $e) {
                $this->line("  <fg=yellow>○</> {$description} (skipped)");
            }
        }

        $this->line('');
        info('✓ Cache clearing complete!');
    }

    private function listArtisanCommands(): void
    {
        $this->line('');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('<fg=yellow>  🛤️  AVAILABLE ARTISAN COMMANDS</>');
        $this->line('<fg=cyan>═══════════════════════════════════════════════════════════</>');
        $this->line('');

        $result = Process::run('php artisan list --raw');
        $commands = collect(explode("\n", $result->output()))
            ->filter(fn($line) => str_contains($line, 'flambient') || str_contains($line, 'imagen') || str_contains($line, 'home'))
            ->values();

        $this->line('<fg=white>D-MEC Commands:</>');
        foreach ($commands as $cmd) {
            $this->line("  <fg=yellow>{$cmd}</>");
        }

        $this->line('');
        $this->line('<fg=white>Quick Reference:</>');
        $this->line('  <fg=gray>php artisan home</> - This dashboard');
        $this->line('  <fg=gray>php artisan flambient:process</> - Main workflow');
        $this->line('  <fg=gray>php artisan flambient:process --local</> - Local only');
        $this->line('');
    }

    private function showPhpInfo(): void
    {
        $info = [
            ['Setting', 'Value'],
            ['PHP Version', PHP_VERSION],
            ['PHP Binary', PHP_BINARY],
            ['PHP SAPI', PHP_SAPI],
            ['Memory Limit', ini_get('memory_limit')],
            ['Max Execution Time', ini_get('max_execution_time') . 's'],
            ['Upload Max Size', ini_get('upload_max_filesize')],
            ['Post Max Size', ini_get('post_max_size')],
            ['Timezone', date_default_timezone_get()],
            ['OPcache', extension_loaded('Zend OPcache') ? 'Enabled' : 'Disabled'],
        ];

        table(['Setting', 'Value'], array_slice($info, 1));
    }

    // ═══════════════════════════════════════════════════════════
    // SETTINGS MENU
    // ═══════════════════════════════════════════════════════════

    private function settingsMenu(): void
    {
        $choice = select(
            label: '⚙️  Settings & Configuration',
            options: [
                'view' => '📋 View Current Configuration',
                'edit' => '✏️  Edit .env File',
                'imagemagick' => '🪄 ImageMagick Settings',
                'imagen' => '☁️  Imagen AI Settings',
                'storage' => '📁 Storage Paths',
                'migrate' => '🔄 Run Database Migrations',
                'back' => '← Back to Main Menu',
            ],
        );

        match ($choice) {
            'view' => $this->viewConfiguration(),
            'edit' => $this->editEnvFile(),
            'imagemagick' => $this->configureImageMagick(),
            'imagen' => $this->configureImagen(),
            'storage' => $this->showStoragePaths(),
            'migrate' => $this->runMigrations(),
            'back' => null,
        };
    }

    private function viewConfiguration(): void
    {
        $this->showEnvironment();
    }

    private function editEnvFile(): void
    {
        $editor = env('EDITOR', 'nano');
        $envPath = base_path('.env');
        
        $editorChoice = select(
            label: 'Select editor',
            options: [
                'nano' => 'nano (terminal)',
                'vim' => 'vim (terminal)',
                'code' => 'VS Code',
                'open' => 'Default app (TextEdit)',
            ],
            default: 'nano'
        );

        $editor = match($editorChoice) {
            'nano' => 'nano',
            'vim' => 'vim',
            'code' => 'code',
            'open' => 'open -t',
        };

        info("Opening .env with {$editorChoice}...");
        
        if ($editorChoice === 'code') {
            Process::run("{$editor} '{$envPath}'");
        } else {
            Process::tty()->run("{$editor} '{$envPath}'");
        }
    }

    private function configureImageMagick(): void
    {
        $settings = [
            ['Setting', 'Current Value', 'Description'],
            ['IMAGEMAGICK_BINARY', env('IMAGEMAGICK_BINARY', 'magick'), 'Path to magick executable'],
            ['IMAGEMAGICK_LEVEL_LOW', env('IMAGEMAGICK_LEVEL_LOW', '40%'), 'Lower level adjustment'],
            ['IMAGEMAGICK_LEVEL_HIGH', env('IMAGEMAGICK_LEVEL_HIGH', '140%'), 'Upper level adjustment'],
            ['IMAGEMAGICK_GAMMA', env('IMAGEMAGICK_GAMMA', '1.0'), 'Gamma correction value'],
            ['IMAGEMAGICK_OUTPUT_PREFIX', env('IMAGEMAGICK_OUTPUT_PREFIX', 'flambient'), 'Output filename prefix'],
            ['IMAGEMAGICK_DARKEN_EXPORT', env('IMAGEMAGICK_DARKEN_EXPORT', 'true'), 'Export darken layer'],
            ['IMAGEMAGICK_DARKEN_SUFFIX', env('IMAGEMAGICK_DARKEN_SUFFIX', '_tmp'), 'Darken file suffix'],
        ];

        table(['Setting', 'Current Value', 'Description'], array_slice($settings, 1));

        $this->line('');
        note('Edit .env file to change these settings');
        
        if (confirm('Edit .env now?')) {
            $this->editEnvFile();
        }
    }

    private function configureImagen(): void
    {
        $this->showImagenConfig();
        
        $this->line('');
        
        if (confirm('Test Imagen API connection?')) {
            $this->testAllConnections();
        }
    }

    private function showStoragePaths(): void
    {
        $paths = [
            ['Path', 'Location', 'Exists'],
            ['Storage Root', storage_path(), is_dir(storage_path()) ? '✓' : '✗'],
            ['Flambient Output', storage_path('flambient'), is_dir(storage_path('flambient')) ? '✓' : '✗'],
            ['Logs', storage_path('logs'), is_dir(storage_path('logs')) ? '✓' : '✗'],
            ['Framework Cache', storage_path('framework/cache'), is_dir(storage_path('framework/cache')) ? '✓' : '✗'],
            ['Tether Scripts', resource_path('exe'), is_dir(resource_path('exe')) ? '✓' : '✗'],
            ['Public Uploads', public_path('uploads'), is_dir(public_path('uploads')) ? '✓' : '✗'],
        ];

        table(['Path', 'Location', 'Exists'], array_slice($paths, 1));
    }

    private function runMigrations(): void
    {
        if (!confirm('Run database migrations?')) {
            return;
        }

        info('Running migrations...');
        $this->call('migrate', ['--force' => true]);
    }

    // ═══════════════════════════════════════════════════════════
    // UTILITIES
    // ═══════════════════════════════════════════════════════════

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function exitApp(): void
    {
        $this->line('');
        $this->line('<fg=cyan>Thanks for using D-MEC Image Processor!</>');
        $this->line('<fg=gray>Run "php artisan home" to return</>');
        $this->line('');
        outro('Goodbye! 👋');
        exit(0);
    }
}
