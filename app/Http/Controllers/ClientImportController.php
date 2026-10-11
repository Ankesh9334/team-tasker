<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessClientImportJob;
use App\Models\Client;
use App\Models\ClientImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClientImportController extends Controller
{
    /**
     * Show CSV upload form (Wizard Step 1).
     */
    public function index()
    {
        return view('admin.clients.import.index');
    }

    /**
     * Parse CSV and show Column Mapping & Preview (Wizard Step 2).
     */
    public function parse(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:20480', // 20MB max
            'duplicate_handling' => 'required|in:skip,update',
        ]);

        $file = $request->file('csv_file');
        $originalName = $file->getClientOriginalName();
        $storedPath = $file->store('client_imports', 'local');

        $fullPath = Storage::disk('local')->path($storedPath);
        $handle = fopen($fullPath, 'r');

        if (!$handle) {
            return back()->with('error', 'Failed to read uploaded CSV file.');
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return back()->with('error', 'The uploaded CSV file is empty.');
        }

        // Clean headers
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        $headers = array_map('trim', $header);

        // Standard database target fields
        $targetFields = [
            'name' => 'Client Name (Required)',
            'email' => 'Email Address (Required)',
            'company_name' => 'Company Name',
            'phone' => 'Phone Number',
            'client_type' => 'Client Type (e.g. ERP, Ecommerce)',
            'status' => 'Status (Active, Inactive, Prospect)',
            'website' => 'Website URL',
        ];

        // Auto-detect default mapping based on common column names
        $autoMapping = [];
        foreach ($targetFields as $key => $label) {
            foreach ($headers as $idx => $headerCol) {
                $clean = strtolower(str_replace([' ', '_', '-'], '', $headerCol));
                if (
                    ($key === 'name' && in_array($clean, ['name', 'client', 'clientname', 'fullname'])) ||
                    ($key === 'email' && in_array($clean, ['email', 'emailaddress', 'mail'])) ||
                    ($key === 'company_name' && in_array($clean, ['company', 'companyname', 'organization', 'org'])) ||
                    ($key === 'phone' && in_array($clean, ['phone', 'phonenumber', 'mobile', 'tel', 'telephone'])) ||
                    ($key === 'client_type' && in_array($clean, ['clienttype', 'type', 'industry', 'category'])) ||
                    ($key === 'status' && in_array($clean, ['status', 'clientstatus', 'state'])) ||
                    ($key === 'website' && in_array($clean, ['website', 'site', 'url', 'web']))
                ) {
                    $autoMapping[$key] = $idx;
                    break;
                }
            }
        }

        // Read sample rows and calculate quick validation preview
        $sampleRows = [];
        $totalRows = 0;
        $validCount = 0;
        $invalidCount = 0;
        $duplicateCount = 0;
        $previewErrors = [];

        $emailColIdx = $autoMapping['email'] ?? null;
        $nameColIdx = $autoMapping['name'] ?? null;

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row, fn($val) => trim($val) !== ''))) {
                continue;
            }

            $totalRows++;

            if (count($sampleRows) < 5) {
                $sampleRows[] = $row;
            }

            // Quick validation for preview
            $rowEmail = ($emailColIdx !== null && isset($row[$emailColIdx])) ? strtolower(trim($row[$emailColIdx])) : '';
            $rowName = ($nameColIdx !== null && isset($row[$nameColIdx])) ? trim($row[$nameColIdx]) : '';

            $isRowInvalid = false;
            if (empty($rowName)) {
                $isRowInvalid = true;
                if (count($previewErrors) < 10) {
                    $previewErrors[] = ['row' => $rowNum, 'column' => 'Name', 'value' => '', 'error' => 'Name is missing'];
                }
            }

            if (empty($rowEmail) || !filter_var($rowEmail, FILTER_VALIDATE_EMAIL)) {
                $isRowInvalid = true;
                if (count($previewErrors) < 10) {
                    $previewErrors[] = ['row' => $rowNum, 'column' => 'Email', 'value' => $rowEmail, 'error' => 'Invalid email format'];
                }
            }

            if ($isRowInvalid) {
                $invalidCount++;
            } else {
                if (Client::where('email', $rowEmail)->exists()) {
                    $duplicateCount++;
                } else {
                    $validCount++;
                }
            }
        }

        fclose($handle);

        $duplicateHandling = $request->input('duplicate_handling', 'skip');

        return view('admin.clients.import.map', compact(
            'storedPath',
            'originalName',
            'headers',
            'targetFields',
            'autoMapping',
            'sampleRows',
            'totalRows',
            'validCount',
            'invalidCount',
            'duplicateCount',
            'previewErrors',
            'duplicateHandling'
        ));
    }

    /**
     * Submit mapping and start import job (Wizard Step 3).
     */
    public function process(Request $request)
    {
        $request->validate([
            'stored_path' => 'required|string',
            'original_name' => 'required|string',
            'duplicate_handling' => 'required|in:skip,update',
            'mapping' => 'required|array',
            'mapping.name' => 'required',
            'mapping.email' => 'required',
        ]);

        $import = ClientImport::create([
            'file_name' => $request->original_name,
            'file_path' => $request->stored_path,
            'imported_by' => auth()->id(),
            'status' => 'pending',
            'column_mapping' => $request->mapping,
            'duplicate_handling' => $request->duplicate_handling,
        ]);

        // Dispatch background queue job
        ProcessClientImportJob::dispatch($import->id);

        return redirect()->route('admin.clients.imports.history')
            ->with('success', 'Import has been queued successfully. You can monitor its progress below.');
    }

    /**
     * Import History list.
     */
    public function history()
    {
        $imports = ClientImport::with('user')->latest()->paginate(15);
        return view('admin.clients.import.history', compact('imports'));
    }

    /**
     * Get error details for an import via JSON.
     */
    public function errors($id)
    {
        $import = ClientImport::findOrFail($id);
        return response()->json([
            'file_name' => $import->file_name,
            'status' => $import->status,
            'failed' => $import->failed,
            'errors' => $import->errors ?? [],
        ]);
    }
}
