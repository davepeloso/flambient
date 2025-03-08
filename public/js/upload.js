document.addEventListener('DOMContentLoaded', () => {
    const uploadZone = document.getElementById('upload-zone-main');
    const progressContainer = document.getElementById('upload-progress-container');
    const batchInfo = document.getElementById('batch-info-display');
    const errorDisplay = document.getElementById('error-display');
    
    // Prevent default drag behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Handle drag and drop events
    ['dragenter', 'dragover'].forEach(eventName => {
        uploadZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        uploadZone.addEventListener(eventName, unhighlight, false);
    });

    // Handle file drop
    uploadZone.addEventListener('drop', handleDrop, false);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(e) {
        uploadZone.classList.add('flambient-upload-zone--active');
    }

    function unhighlight(e) {
        uploadZone.classList.remove('flambient-upload-zone--active');
    }

    function handleDrop(e) {
        const files = [...e.dataTransfer.files];
        uploadFiles(files);
    }

    function uploadFiles(files) {
        // Validate files
        const maxFileSize = parseInt(uploadZone.dataset.maxFileSize);
        const maxBatchSize = parseInt(uploadZone.dataset.maxBatchSize);
        let totalSize = 0;

        const validFiles = files.filter(file => {
            totalSize += file.size;
            if (file.size > maxFileSize) {
                showError(`File ${file.name} exceeds maximum size of 8MB`);
                return false;
            }
            if (!file.type.match(/^image\/(jpeg|jpg)$/)) {
                showError(`File ${file.name} is not a JPEG image`);
                return false;
            }
            return true;
        });

        if (totalSize > maxBatchSize) {
            showError('Total batch size exceeds maximum of 1GB');
            return;
        }

        if (validFiles.length === 0) {
            return;
        }

        // Prepare form data
        const formData = new FormData();
        validFiles.forEach(file => {
            formData.append('files[]', file);
        });

        // Show progress container
        progressContainer.style.display = 'block';
        updateProgress('Uploading files...');

        // Upload files
        fetch('/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => {
            const reader = response.body.getReader();
            return readStream(reader);
        })
        .catch(error => {
            showError('Upload failed: ' + error.message);
        });
    }

    function readStream(reader) {
        const decoder = new TextDecoder();
        
        function processStream({ done, value }) {
            if (done) {
                return;
            }

            const chunk = decoder.decode(value);
            const lines = chunk.split('\n');

            lines.forEach(line => {
                if (line.startsWith('data: ')) {
                    const data = JSON.parse(line.slice(6));
                    handleStreamUpdate(data);
                }
            });

            return reader.read().then(processStream);
        }

        return reader.read().then(processStream);
    }

    function handleStreamUpdate(data) {
        if (!data) return;

        const { timestamp, message, type } = data;
        
        if (message === 'END_STREAM') {
            return;
        }

        updateProgress(`[${timestamp}] ${message}`, type);

        if (message.includes('Batch ID:')) {
            const batchId = message.split('Batch ID:')[1].trim();
            updateBatchInfo(batchId);
        }
    }

    function updateProgress(message, type = 'info') {
        const line = document.createElement('div');
        line.className = 'flambient-terminal-line';
        
        const prompt = document.createElement('span');
        prompt.className = 'flambient-prompt';
        prompt.textContent = '> ';
        
        const text = document.createElement('span');
        text.className = `flambient-text flambient-text--${type}`;
        text.textContent = message;
        
        line.appendChild(prompt);
        line.appendChild(text);
        
        const output = progressContainer.querySelector('.flambient-terminal-output');
        output.appendChild(line);
        output.scrollTop = output.scrollHeight;
    }

    function updateBatchInfo(batchId) {
        batchInfo.style.display = 'block';
        const batchIdElement = batchInfo.querySelector('.flambient-value');
        batchIdElement.textContent = batchId;
    }

    function showError(message) {
        errorDisplay.style.display = 'block';
        const errorMessage = errorDisplay.querySelector('.flambient-error-message');
        errorMessage.textContent = message;
    }
});
