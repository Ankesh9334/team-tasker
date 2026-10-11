<x-admin title="Import Clients — CSV Upload">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Import Clients</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Upload a CSV file to bulk import client records.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.imports.history') }}" class="btn-premium btn-premium-secondary px-4 py-2">
                <i class="fas fa-history me-1"></i> Import History
            </a>
        </div>
    </div>

    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        {{-- Wizard Step Indicator --}}
        <div class="d-flex align-items-center justify-content-center mb-5 gap-2">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">1</div>
                <span class="text-high fw-semibold" style="font-size: 0.85rem;">Upload CSV</span>
            </div>
            <div style="width: 60px; height: 2px; background: var(--border-main);"></div>
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-input); color: var(--text-low); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; border: 1px solid var(--border-main);">2</div>
                <span class="text-low" style="font-size: 0.85rem;">Map Columns</span>
            </div>
            <div style="width: 60px; height: 2px; background: var(--border-main);"></div>
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-input); color: var(--text-low); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; border: 1px solid var(--border-main);">3</div>
                <span class="text-low" style="font-size: 0.85rem;">Processing</span>
            </div>
        </div>

        <form action="{{ route('admin.clients.imports.parse') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf

            {{-- Drop Zone --}}
            <div id="dropZone" style="border: 2px dashed var(--border-main); border-radius: var(--radius-lg); padding: 60px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: var(--bg-input);"
                 onclick="document.getElementById('csvFileInput').click()">
                <div>
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--primary); margin-bottom: 16px;"></i>
                    <h4 class="text-high fw-semibold mb-2">Drop your CSV file here</h4>
                    <p class="text-low mb-3" style="font-size: 0.9rem;">or click to browse — Max 20MB, .csv format only</p>
                    <div id="fileInfo" style="display: none;" class="mt-3">
                        <div style="display: inline-flex; align-items: center; gap: 10px; padding: 10px 20px; background: rgba(var(--primary-rgb), 0.1); border-radius: var(--radius-md); border: 1px solid rgba(var(--primary-rgb), 0.2);">
                            <i class="fas fa-file-csv" style="font-size: 1.5rem; color: var(--primary);"></i>
                            <div style="text-align: left;">
                                <div class="text-high fw-medium" id="fileName" style="font-size: 0.85rem;"></div>
                                <div class="text-low" id="fileSize" style="font-size: 0.75rem;"></div>
                            </div>
                            <button type="button" onclick="event.stopPropagation(); clearFile();" class="ms-2 border-0 bg-transparent" style="color: var(--text-low); cursor: pointer;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <input type="file" name="csv_file" id="csvFileInput" accept=".csv,.txt" style="display: none;" required>

            {{-- Options --}}
            <div class="row g-4 mt-4">
                <div class="col-md-6">
                    <label class="heading-label d-block mb-2 text-high">Duplicate Handling <span class="text-danger">*</span></label>
                    <x-select name="duplicate_handling" required>
                        <option value="skip" selected class="bg-dark">Skip Duplicates (by email)</option>
                        <option value="update" class="bg-dark">Update Existing Records</option>
                    </x-select>
                    <div class="text-low mt-2" style="font-size: 0.75rem;">
                        <i class="fas fa-info-circle me-1"></i> Duplicates are matched by email address.
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-4 pt-3" style="border-top: 1px solid var(--border-main);">
                <button type="submit" class="btn-premium btn-premium-primary px-5 py-2 shadow-sm" id="uploadBtn" disabled>
                    <i class="fas fa-arrow-right me-2"></i> Continue to Column Mapping
                </button>
            </div>
        </form>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('csvFileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadBtn = document.getElementById('uploadBtn');

        ['dragover', 'dragenter'].forEach(evt => {
            dropZone.addEventListener(evt, e => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--primary)';
                dropZone.style.background = 'rgba(var(--primary-rgb), 0.05)';
            });
        });

        ['dragleave', 'drop'].forEach(evt => {
            dropZone.addEventListener(evt, e => {
                e.preventDefault();
                dropZone.style.borderColor = 'var(--border-main)';
                dropZone.style.background = 'var(--bg-input)';
            });
        });

        dropZone.addEventListener('drop', e => {
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                showFile(files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                showFile(this.files[0]);
            }
        });

        function showFile(file) {
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024).toFixed(1) + ' KB';
            fileInfo.style.display = 'inline-block';
            uploadBtn.disabled = false;
        }

        function clearFile() {
            fileInput.value = '';
            fileInfo.style.display = 'none';
            uploadBtn.disabled = true;
        }
    </script>
</x-admin>
