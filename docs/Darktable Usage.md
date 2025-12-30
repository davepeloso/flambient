# # Usage Guide Darktable
### Single Image Export
To apply your "flambient" style to a single image:
```bash
darktable-cli "input.jpg" "output_fixed.jpg" --style "re-ajustments"
```
### Batch Processing (Folder)
To process all .jpg files in your current directory and append _fixed to the filename:
```bash
for f in *.jpg; do 
  darktable-cli "$f" "${f%.jpg}_fixed.jpg" --style "re-ajustments"
done
```

# Troubleshooting & Maintenance
**Error:** can't init develop system
* This usually means the /Applications/darktable.app was moved or updated.
* **Fix:** Ensure the paths inside /usr/local/bin/darktable-cli still match the location of your app bundle.

⠀**Style not found?**
* Styles are case-sensitive. Ensure the name matches exactly what is shown in the Darktable "Styles" panel.
* If the style is in a category (e.g., RealEstate|re-ajustments), you must use the full string: --style "RealEstate|re-ajustments".

⠀**Updating Darktable**
* If you install a new version via DMG, the wrapper script should continue to work as long as the app name remains darktable.app.


⠀ Great! Since we’re thinking about the full **Flambient** workflow, here is a professional automation script.
Real estate photography usually involves a high volume of files. This script handles the batching, creates a clean folder structure, and uses **macOS system notifications** so you can grab a coffee while it works and know exactly when it’s done.
### ### Theflambient-process Script
Save this as `/usr/local/bin/flambient-process and chmod +x it.`

```bash
#!/bin/bash
# Configuration
STYLE="re-ajustments"
OUT_FOLDER="01_Exports"

# 1. Setup Folders
mkdir -p "$OUT_FOLDER"

echo "📸 Starting Flambient Batch: $(ls *.jpg 2>/dev/null | wc -l) files"

# 2. Process Images
for f in *.jpg; do
    # Skip already exported files
    [[ "$f" == *"_fixed.jpg" ]] && continue
    
    echo "Processing $f..."
    darktable-cli "$f" "$OUT_FOLDER/${f%.jpg}_fixed.jpg" --style "$STYLE"
done

# 3. macOS Desktop Notification
osascript -e "display notification \"All images processed with $STYLE\" with title \"Darktable Batch Complete\" sound name \"Glass\""

echo "✅ Done. Files moved to $OUT_FOLDER"
```

### Why this is helpful for your workflow:
* **Non-Destructive Routing:** It places everything in a 01_Exports subfolder so your working directory stays clean.
* **Idempotency:** The [[ "$f" == *"_fixed.jpg" ]] && continue line ensures that if you run the script twice, it won't try to "re-process" the images it already finished.
* **Background Feedback:** The osascript line sends a native macOS notification banner to the top right of your screen once the job is finished.

⠀One final tip for your README:
If you ever need to change the style you're using (e.g., you make a "re-ajustments-v2"), you only have to change the STYLE= variable at the top of this script once.

# HOTFOLDER
Setting up a "Hot Folder" on macOS is best done using **Folder Actions**. This allows macOS to monitor a folder and trigger your darktable-cli script the moment a new image is dropped into it.
### 1\. Create the Automation Script
First, we need a specific script that macOS can call. Save this as dt-hotfolder.sh in a safe place (like your home directory).

```bash
#!/bin/bash
# Configuration
STYLE="re-ajustments"
EXPORT_DIR="processed"

# Move to the folder that triggered the action
cd "$1"
mkdir -p "$EXPORT_DIR"

# Loop through new files passed by macOS
shift # Remove the first argument (the folder path)
for f in "$@"; do
    ext="${f##*.}"
    if [[ "$ext" =~ ^(jpg|JPG|arw|ARW)$ ]]; then
        filename=$(basename "$f")
        /usr/local/bin/darktable-cli "$f" "$EXPORT_DIR/${filename%.*}_fixed.jpg" --style "$STYLE"
    fi
done
```

**Make it executable:** `chmod +x ~/dt-hotfolder.sh`
### 2\. Connect it to macOS Folder Actions
To make this work "live," we use the **Automator** app:
1. Open **Automator** (Cmd + Space, type "Automator").
2. Select **New Document** → **Folder Action**.
3. At the top, where it says "Folder Action receives files and folders added to:", choose your target flambient folder.
4. In the left sidebar, search for **"Run Shell Script"** and drag it to the right.
5. Set **Pass input:** to **"as arguments"**.
6. Paste the following into the box:

```bash
~/dt-hotfolder.sh "$1" "$@"
```

**Save** the Automator action as "Darktable-Hotfolder".

### 3\. How the Workflow Looks
1. You finish your "flambient" shots and drop them into your watched folder.
2. macOS detects the new files and silently wakes up darktable-cli in the background.
3. A few seconds later, a processed/ folder appears inside with all your edits applied.
