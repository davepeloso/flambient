#!/bin/zsh


osascript -e 'tell application "Finder"
to activate' -e 'set input_dir to choose folder with prompt
"Drag and drop a folder here:"'

input_dir=$(osascript -e 'tell application "Finder" to activate' -e 'choose folder with prompt "Drag and drop a folder here:" as string')

# Remove the "alias " prefix if present
input_dir=${input_dir#alias }

# Convert POSIX path to Unix path
input_dir=$(echo "$input_dir" | sed 's/:/\//g')

# Remove trailing colon if present
input_dir=${input_dir%:}

echo "Selected folder: $input_dir"

# Ensure the input_dir is set
if [ -z "$input_dir" ]; then
    echo "No folder selected. Exiting."
    exit 1
fi

# Rest of your script continues here...



# Main loop for processing images
for file_name in "$input_dir"/*.{jpg,jpeg,png}(N); do
    echo "# Skip if no files match"
    [[ ! -e "$file_name" ]] && continue

    echo "# Extract metadata"
    current_metadata=$(identify -format "%[EXIF:ExposureMode]" "$file_name" 2>/dev/null)

    echo "# Compare current metadata with the previous one"
    if [[ "$current_metadata" != "$previous_metadata" && -n "$previous_metadata" ]]; then
        echo "# Start a new group if metadata differs"
        ((group_number++))
        group_file="$output_dir/group_$group_number.txt"
    fi

    echo "# Append the file name to the current group file"
    echo "$dir_path/$(basename "$file_name")" >> "$group_file"

    echo "# Update previous metadata for the next iteration"
    previous_metadata="$current_metadata"
done

# Process group files
group_array=("$output_dir"/*.txt)

if [ ${#group_array[@]} -eq 0 ]; then
    echo "No group_files found in $output_dir"
    exit 1
fi

# Initialize counter for file naming
counter=1000

for ((i=0; i<${#group_array[@]}-1; i+=2)); do
    # Initialize variables for file naming
    ambient="Ambient${counter}.jpg"
    mask="Mask$((counter + 3)).png"
    flash="Flash$((counter + 1)).jpg"
    flambient="Flambient$((counter + 2)).jpg"

    # Echo file names for debugging
    echo "Processing group $((i/2 + 1))"
    echo "Ambient: $ambient"
    echo "Mask: $mask"
    echo "Flash: $flash"
    echo "Flambient: $flambient"

    dymc_group_file_1="${group_array[i]}"
    dymc_group_file_2="${group_array[i+1]}"
    txt_grp_output_1="${dymc_group_file_1%.*}.png"
    txt_grp_output_2="${dymc_group_file_2%.*}.jpg"

    if [[ -f "$dymc_group_file_1" ]]; then
        echo "Processing $dymc_group_file_1"
        if ! magick "@$dymc_group_file_1" "$output_dir/$ambient"; then
            echo "Error processing $dymc_group_file_1 for ambient image"
        fi
        if ! magick "@$dymc_group_file_1" -channel B -separate -level 30%,100%,0.5 "$output_dir/$mask"; then
            echo "Error processing $dymc_group_file_1 for mask"
        fi
    else
        echo "Problem with the mask file or group txt file $(basename "$txt_grp_output_1")"
    fi

    if [[ -f "$dymc_group_file_2" ]]; then
        echo "Processing $dymc_group_file_2"
        if ! magick @"$dymc_group_file_2" -compose lighten -composite "$output_dir/$flash"; then
            echo "Error processing $dymc_group_file_2 for flash image"
        fi
    else
        echo "Problem with the flashes file or group txt file $(basename "$txt_grp_output_2")"
    fi

    if [[ -f "$output_dir/$mask" ]]; then
        echo "Making highlight / window mask"
        if ! magick "$output_dir/$flash" "$output_dir/$mask" -compose CopyOpacity -composite -channel A "$output_dir/tmp_$mask"; then
            echo "Error creating highlight/window mask"
        fi
    else
        echo "Something wrong with the flashes or Mask file or group txt file"
    fi

    if ! magick \( "$output_dir/$flash" "$output_dir/$ambient" -compose Luminize -composite \) \
                \( "$output_dir/tmp_$mask" -compose Over \) -composite \
                "$flambient_dir/$flambient"; then
        echo "Error creating flambient image"
    fi

    # Increment counter for next iteration
    counter=$((counter + 4))
done