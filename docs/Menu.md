# FlaZsh Menu Structure

## Main Menu
| Item | Action |
|------|--------|
| Image Processing | Opens `imageProcessingMenu()` submenu |
| Imagen AI Integration | Opens `imagenMenu()` submenu |
| Database & Jobs | Opens `databaseMenu()` submenu |
| Camera Tethering & Import | Opens `tetheringMenu()` submenu |
| Manual & Documentation | Opens `manualMenu()` submenu |
| Debug & Diagnostics | Opens `debugMenu()` submenu |
| Settings & Configuration | Opens `settingsMenu()` submenu |
| Exit Application | Exits the app |

---

## Image Processing Menu
| Item | Method | Action | Notes |
|------|--------|--------|-------|
| Run Flambient Workflow | `runFlambientWorkflow()` | Runs `flambient:process` command with mode selection | Works |
|[X] Upload & Extract EXIF | `runUploadWorkflow()` | **Opens browser to `/upload`** | **BROKEN - Route doesn't exist** |
|[X] Classify Exposure Stacks | `runClassifyWorkflow()` | **Opens browser to `/cull`** | **BROKEN - Route doesn't exist** |
| Generate ImageMagick Scripts | `runGenerateScripts()` | Lists batches, warns "not yet fully implemented" | Incomplete |
| Execute Processing Scripts | `runExecuteScripts()` | Lists projects, warns "not yet fully implemented" | Incomplete |
|[X]Reprocess EXIF Data | `reprocessExifData()` | Runs `images:reprocess-exif` command | Should verify command exists |
| Browse Output Folders | `browseOutputFolders()` | Opens `storage/flambient` in Finder | Works |

### Flambient Workflow Sub-options
| Option | Flag |
|--------|------|
| Local Only (ImageMagick blending) | `--local` |
| Full Workflow (with Imagen AI) | (none) |
| Interactive (guided prompts) | (none) |

---

## Imagen AI Integration Menu
| Item | Method | Action | Notes |
|------|--------|--------|-------|
| List Available Profiles | `listImagenProfiles()` | GET `/profiles/` from Imagen API | Works if API key set |
| View Recent Projects | `listImagenProjects()` | GET `/projects/` from Imagen API | Works if API key set |
| Check Job Status (by UUID) | `checkImagenJobStatus()` | Prompts for UUID, fetches project details | Works |
| View Local Job Records | `viewLocalImagenJobs()` | Queries `imagen_jobs` table | May fail if table doesn't exist |
| [1]Process Images with Imagen AI | `uploadToImagen()` | Lists output folders, warns "requires ImagenClient" | Incomplete |
| [2] Export & Download Results | `exportFromImagen()` | POST to export, then GET download links | Works |
| View Imagen Config | `showImagenConfig()` | Displays .env Imagen settings in table | Works |
| Test API Endpoints | `testImagenApiEndpoints()` | Tests profiles/projects/account endpoints | Works |

---

## Database & Jobs Menu
| Item | Method | Action | Notes |
|------|--------|--------|-------|
| Database Statistics | `showDatabaseStats()` | Shows record counts for all tables | Works |
| View Batches | `viewBatches()` | Lists last 15 batches with status | Works |
| View Images | `viewImages()` | Lists images with EXIF metadata | Works |
| View Exposure Stacks | `viewStacks()` | Lists last 15 exposure stacks | Works |
| View Laravel Jobs Queue | `viewJobs()` | Shows pending jobs from `jobs` table | Works |
| Run Custom Query (SELECT only) | `runRawQuery()` | Executes user-provided SELECT query | Works |
| List All Tables | `listDatabaseTables()` | Lists all SQLite tables with counts | Works |
| Open Database File | `openDatabaseFile()` | Opens in Finder/TablePlus/sqlite3 | Works |

---

## Camera Tethering & Import Menu
| Item | Method | Action | Notes |
|------|--------|--------|-------|
| Live Capture (photos-capture-live) | `runTetherScript()` | Runs `resources/exe/photos-capture-live` | Script may not exist |
| Sync from Photos.app (photos-capture-sync) | `runTetherScript()` | Runs `resources/exe/photos-capture-sync` | Script may not exist |
| [X]Debug Tethering (photos-tether-debug) | `runTetherScript()` | Runs `resources/exe/photos-tether-debug` | Script may not exist |
| [X] Check Photos.app Status | `checkPhotosStatus()` | Checks if Photos.app running, camera connected | Works |
| [X] Import from Folder | `importFromFolder()` | Scans folder for JPEGs, warns "not yet integrated" | Incomplete |
| Tethering Help & Requirements | `showTetheringHelp()` | Displays help text | Works |

---

## Manual & Documentation Menu
| Item | Method | Action |
|------|--------|--------|
| Application Overview | `showOverview()` | Displays app overview text |
| Quick Start Guide | `showQuickStart()` | Displays 5-step quickstart |
| [X] D-MEC Theory & Background | `showDMECTheory()` | Explains Flambient technique |
| Processing Workflow Guide | `showWorkflowGuide()` | Shows 6-phase workflow diagram |
| ImageMagick Reference | `showImageMagickGuide()` | ImageMagick commands reference |
| Imagen AI Integration Guide | `showImagenGuide()` | Imagen API usage guide |
| Troubleshooting Guide | `showTroubleshooting()` | Common problems & solutions |
| Frequently Asked Questions | `showFAQ()` | FAQ list |
| Version History | `showChangelog()` | Version changelog |

---

## Debug & Diagnostics Menu
| Item | Method | Action |
|------|--------|--------|
| System Health Check | `runHealthCheck()` | Checks PHP, DB, exiftool, magick, API key |
| Environment Variables | `showEnvironment()` | Shows key .env values in table |
| Test API Connections | `testAllConnections()` | Tests DB, Imagen API, CLI tools |
| Check Dependencies | `checkDependencies()` | Lists CLI tools, PHP extensions, Composer packages |
| View Recent Logs | `viewRecentLogs()` | Tails `laravel.log` with optional filter |
| Clear Caches | `clearCaches()` | Runs cache/config/view/route/event clear |
| List Artisan Commands | `listArtisanCommands()` | Lists D-MEC related artisan commands |
| PHP Info | `showPhpInfo()` | Shows PHP configuration in table |

---

## Settings & Configuration Menu
| Item | Method | Action |
|------|--------|--------|
| View Current Configuration | `viewConfiguration()` | Shows all config in table (duplicate of Debug > Environment) |
| Edit .env File | `editEnvFile()` | Opens .env in VS Code/micro/TextEdit |
| ImageMagick Settings | `configureImageMagick()` | Shows ImageMagick .env settings, offers to edit |
| Imagen AI Settings | `configureImagen()` | Shows Imagen .env settings, offers API test |
| Storage Paths | `showStoragePaths()` | Shows storage locations and if they exist |
| Run Database Migrations | `runMigrations()` | Runs `migrate --force` |

---

## Issues Found

### Broken Features (Routes Don't Exist)
1. **Upload & Extract EXIF** - Opens `/upload` which doesn't exist in `routes/web.php`
2. **Classify Exposure Stacks** - Opens `/cull` which doesn't exist in `routes/web.php`

### Incomplete Features
1. **Generate ImageMagick Scripts** - Has "not yet fully implemented" warning
2. **Execute Processing Scripts** - Has "not yet fully implemented" warning
3. **Process Images with Imagen AI** - Requires ImagenClient service that may not be complete
4. **Import from Folder** - Has "not yet integrated with CLI" warning

### Potential Duplicates
1. `showEnvironment()` (Debug menu) vs `viewConfiguration()` (Settings menu) - nearly identical
2. `showImagenConfig()` (Imagen menu) vs `configureImagen()` (Settings menu) - overlap

### Menu Organization Suggestions
- "Reprocess EXIF Data" could move to Database menu (it's a data operation)
- "View Local Job Records" in Imagen menu queries DB - could be in Database menu
- Debug > "Environment Variables" and Settings > "View Current Configuration" are duplicates
