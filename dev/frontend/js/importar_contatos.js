document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('arquivo_xlsx');
    const fileNameDisplay = document.getElementById('file-name-display');

    if (dropZone && fileInput && fileNameDisplay) {
        dropZone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.background = '#e9ecef';
                dropZone.style.borderColor = '#0d6efd';
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.style.background = '#f9f9f9';
                dropZone.style.borderColor = '#ccc';
            }, false);
        });

        dropZone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length) {
                fileInput.files = files;
                updateFileName(files[0].name);
            }
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length) {
                updateFileName(e.target.files[0].name);
            }
        });

        function updateFileName(name) {
            fileNameDisplay.textContent = "Arquivo selecionado: " + name;
        }
    }
});
