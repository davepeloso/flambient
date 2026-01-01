# flaZsh Installation Guide

## Global Command Setup

To access flaZsh from anywhere on your computer, you need to add it to your system PATH.

### Option 1: Symlink to /usr/local/bin (Recommended for macOS/Linux)

This makes the `flazsh` command available globally:

```bash
# Create symlink
sudo ln -s "$(pwd)/bin/flazsh" /usr/local/bin/flazsh

# Verify installation
which flazsh
# Should output: /usr/local/bin/flazsh

# Test it
flazsh
```

### Option 2: Add to PATH via Shell Configuration (macOS/Linux)

Add the flaZsh bin directory to your PATH:

#### For Zsh (macOS default):
```bash
# Add to ~/.zshrc
echo 'export PATH="$HOME/flaZsh/bin:$PATH"' >> ~/.zshrc

# Reload shell configuration
source ~/.zshrc

# Test it
flazsh
```

#### For Bash:
```bash
# Add to ~/.bashrc or ~/.bash_profile
echo 'export PATH="$HOME/flaZsh/bin:$PATH"' >> ~/.bashrc

# Reload shell configuration
source ~/.bashrc

# Test it
flazsh
```

### Option 3: Create an Alias

Add an alias to your shell configuration:

#### For Zsh:
```bash
# Add to ~/.zshrc
echo 'alias flazsh="php /path/to/flaZsh/artisan home"' >> ~/.zshrc
source ~/.zshrc
```

#### For Bash:
```bash
# Add to ~/.bashrc
echo 'alias flazsh="php /path/to/flaZsh/artisan home"' >> ~/.bashrc
source ~/.bashrc
```

## Usage

Once installed globally, you can run flaZsh from anywhere:

```bash
# Launch interactive dashboard
flazsh

# Run any artisan command
flazsh flambient:process
flazsh flambient:process --local
flazsh imagen:fetch-profiles

# Run with options
flazsh --compact
flazsh --no-ascii
```

## First-Time Setup

After installing globally, complete the initial setup:

1. **Configure Environment**
   ```bash
   cd /path/to/flaZsh
   cp .env.example .env
   nano .env  # Edit configuration
   ```

2. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

3. **Run Migrations**
   ```bash
   php artisan migrate
   ```

4. **Configure Imagen AI (Optional)**

   Edit your `.env` file and add:
   ```
   IMAGEN_AI_API_KEY=your_api_key_here
   IMAGEN_PROFILE_KEY=309406
   ```

5. **Verify Installation**
   ```bash
   flazsh
   ```

   Then navigate to: **Debug & Diagnostics → System Health Check**

## Dependencies

Ensure these are installed on your system:

- **PHP 8.2+** with extensions: sqlite3, json, mbstring, curl, openssl
- **Composer** (for dependency management)
- **ExifTool** - `brew install exiftool` (macOS) or `apt install libimage-exiftool-perl` (Linux)
- **ImageMagick 7+** - `brew install imagemagick` (macOS) or `apt install imagemagick` (Linux)

## Camera Tethering (macOS Only)

The following scripts are included in `resources/exe/`:
- `photos-capture-live` - Live capture from tethered camera
- `photos-capture-sync` - Sync from Photos.app library
- `photos-tether-debug` - Debug tethering issues

Access them from: **Camera Tethering & Import** menu

## Troubleshooting

### Command Not Found

If `flazsh` command is not found after installation:

1. **Check PATH**
   ```bash
   echo $PATH
   ```
   Ensure your installation directory is listed.

2. **Reload Shell**
   ```bash
   source ~/.zshrc  # or ~/.bashrc
   ```

3. **Check Symlink**
   ```bash
   ls -la /usr/local/bin/flazsh
   ```

### Permission Denied

If you get permission errors:

```bash
# Make sure the script is executable
chmod +x /path/to/flaZsh/bin/flazsh

# If using symlink, recreate it
sudo rm /usr/local/bin/flazsh
sudo ln -s "$(pwd)/bin/flazsh" /usr/local/bin/flazsh
```

### PHP Version Issues

flaZsh requires PHP 8.2+. Check your version:

```bash
php --version
```

If you have multiple PHP versions, specify the correct one:

```bash
# Edit bin/flazsh and change the shebang or use absolute path
/usr/local/bin/php8.2 artisan home
```

## Uninstall

To remove the global command:

```bash
# Remove symlink
sudo rm /usr/local/bin/flazsh

# Or remove from PATH by editing ~/.zshrc or ~/.bashrc
# Remove the line: export PATH="$HOME/flaZsh/bin:$PATH"
```

## Quick Reference

| Command | Description |
|---------|-------------|
| `flazsh` | Launch interactive dashboard |
| `flazsh flambient:process` | Run full workflow |
| `flazsh flambient:process --local` | Run local-only (no Imagen AI) |
| `flazsh imagen:fetch-profiles` | Fetch Imagen profiles |
| `flazsh --help` | Show all available commands |

## Support

For issues, consult the built-in documentation:
```bash
flazsh
# Navigate to: Manual & Documentation → Troubleshooting Guide
```

---

**flaZsh v2.1.0 "Flazsh Revival"**
D-MEC Image Processor - Laravel Edition
