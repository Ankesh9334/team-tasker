<x-admin title="Import Clients — Column Mapping">
    <div class="top-bar-premium">
        <div>
            <h1 class="h3 fw-semibold mb-1 text-high">Map Columns</h1>
            <p class="text-low mb-0" style="font-size: 0.9rem;">Map your CSV columns to client fields — <span class="text-high fw-medium">{{ $originalName }}</span></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.clients.imports.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="data-grid-wrapper p-4 mb-4" style="background: var(--bg-surface); border: 1px solid var(--border-main); border-radius: var(--radius-lg);">
        {{-- Wizard Step Indicator --}}
        <div class="d-flex align-items-center justify-content-center mb-4 gap-2">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: rgba(16,185,129,0.2); color: #10b981; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;"><i class="fas fa-check" style="font-size: 0.7rem;"></i></div>
                <span class="text-low" style="font-size: 0.85rem;">Upload CSV</span>
            </div>
            <div style="width: 60px; height: 2px; background: var(--primary);"></div>
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">2</div>
                <span class="text-high fw-semibold" style="font-size: 0.85rem;">Map Columns</span>
            </div>
            <div style="width: 60px; height: 2px; background: var(--border-main);"></div>
            <div class="d-flex align-items-center gap-2">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: var(--bg-input); color: var(--text-low); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; border: 1px solid var(--border-main);">3</div>
                <span class="text-low" style="font-size: 0.85rem;">Processing</span>
            </div>
        </div>

        {{-- Validation Preview Stats --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div style="padding: 16px; background: var(--bg-input); border-radius: var(--radius-md); border: 1px solid var(--border-main);">
                    <div class="text-low" style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Total Rows</div>
                    <div class="text-high fw-bold" style="font-size: 1.5rem;">{{ $totalRows }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="padding: 16px; background: rgba(16,185,129,0.05); border-radius: var(--radius-md); border: 1px solid rgba(16,185,129,0.2);">
                    <div style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #10b981;">Valid</div>
                    <div class="fw-bold" style="font-size: 1.5rem; color: #10b981;">{{ $validCount }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="padding: 16px; background: rgba(245,158,11,0.05); border-radius: var(--radius-md); border: 1px solid rgba(245,158,11,0.2);">
                    <div style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #f59e0b;">Duplicates</div>
                    <div class="fw-bold" style="font-size: 1.5rem; color: #f59e0b;">{{ $duplicateCount }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div style="padding: 16px; background: rgba(239,68,68,0.05); border-radius: var(--radius-md); border: 1px solid rgba(239,68,68,0.2);">
                    <div style="font-size: 0.7rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; color: #ef4444;">Invalid</div>
                    <div class="fw-bold" style="font-size: 1.5rem; color: #ef4444;">{{ $invalidCount }}</div>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.clients.imports.process') }}" method="POST">
            @csrf
            <input type="hidden" name="stored_path" value="{{ $storedPath }}">
            <input type="hidden" name="original_name" value="{{ $originalName }}">
            <input type="hidden" name="duplicate_handling" value="{{ $duplicateHandling }}">

            {{-- Column Mapping Table --}}
            <h5 class="text-high fw-semibold mb-3"><i class="fas fa-columns me-2 text-low"></i>Column Mapping</h5>
            <div class="table-responsive mb-4">
                <table class="table data-grid-table">
                    <thead>
                        <tr>
                            <th>DATABASE FIELD</th>
                            <th>MAP TO CSV COLUMN</th>
                            <th>SAMPLE VALUES</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($targetFields as $key => $label)
                            <tr>
                                <td class="text-high fw-medium">
                                    {{ $label }}
                                    @if(in_array($key, ['name', 'email']))
                                        <span class="text-danger">*</span>
                                    @endif
                                </td>
                                <td>
                                    <select name="mapping[{{ $key }}]" class="form-premium-control w-100" style="min-width: 200px;" {{ in_array($key, ['name', 'email']) ? 'required' : '' }}>
                                        <option value="">— Skip / Don't Map —</option>
                                        @foreach($headers as $idx => $header)
                                            <option value="{{ $idx }}" {{ (isset($autoMapping[$key]) && $autoMapping[$key] == $idx) ? 'selected' : '' }}>
                                                {{ $header }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-low" style="font-size: 0.8rem; max-width: 300px;">
                                    @if(isset($autoMapping[$key]))
                                        @foreach(array_slice($sampleRows, 0, 3) as $row)
                                            @if(isset($row[$autoMapping[$key]]))
                                                <span style="display: inline-block; padding: 2px 8px; background: var(--bg-input); border-radius: 4px; margin: 1px 0; font-size: 0.75rem;">{{ \Illuminate\Support\Str::limit($row[$autoMapping[$key]], 40) }}</span>
                                            @endif
                                        @endforeach
                                    @else
                                        <span class="text-low" style="font-style: italic; font-size: 0.75rem;">No auto-detection</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Preview Errors --}}
            @if(!empty($previewErrors))
                <div class="mb-4 p-3" style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.2); border-radius: var(--radius-md);">
                    <h6 class="fw-semibold mb-2" style="color: #ef4444;"><i class="fas fa-exclamation-triangle me-1"></i> Validation Issues (first {{ count($previewErrors) }})</h6>
                    @foreach($previewErrors as $err)
                        <div class="text-low" style="font-size: 0.8rem;">
                            Row {{ $err['row'] }}: <span class="fw-medium text-high">{{ $err['column'] }}</span> — {{ $err['error'] }}
                            @if($err['value']) <code style="font-size: 0.7rem;">"{{ $err['value'] }}"</code> @endif
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Data Preview --}}
            @if(!empty($sampleRows))
                <h5 class="text-high fw-semibold mb-3"><i class="fas fa-table me-2 text-low"></i>Data Preview (first {{ count($sampleRows) }} rows)</h5>
                <div class="table-responsive mb-4">
                    <table class="table data-grid-table">
                        <thead>
                            <tr>
                                @foreach($headers as $header)
                                    <th>{{ strtoupper($header) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sampleRows as $row)
                                <tr>
                                    @foreach($headers as $idx => $header)
                                        <td class="text-high" style="font-size: 0.8rem;">{{ \Illuminate\Support\Str::limit($row[$idx] ?? '', 50) }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Submit --}}
            <div class="mt-4 pt-3 d-flex justify-content-between" style="border-top: 1px solid var(--border-main);">
                <a href="{{ route('admin.clients.imports.index') }}" class="btn-premium btn-premium-secondary px-4 py-2">
                    <i class="fas fa-arrow-left me-1"></i> Re-upload
                </a>
                <button type="submit" class="btn-premium btn-premium-primary px-5 py-2 shadow-sm">
                    <i class="fas fa-rocket me-2"></i> Start Import ({{ $validCount + $duplicateCount }} records)
                </button>
            </div>
        </form>
    </div>
</x-admin>
