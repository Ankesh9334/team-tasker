<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\ClientImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProcessClientImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $importId;
    public int $tries = 2;
    public int $timeout = 600; // 10 minutes max for large imports

    /**
     * Create a new job instance.
     */
    public function __construct(int $importId)
    {
        $this->importId = $importId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $importRecord = ClientImport::find($this->importId);
        if (!$importRecord) {
            return;
        }

        $importRecord->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $filePath = Storage::disk('local')->path($importRecord->file_path);
        if (!file_exists($filePath)) {
            // Also check public disk fallback
            $filePath = Storage::disk('public')->path($importRecord->file_path);
        }

        if (!file_exists($filePath)) {
            $importRecord->update([
                'status' => 'failed',
                'errors' => [['row' => 0, 'column' => 'file', 'value' => '', 'error' => 'File not found on server.']],
                'completed_at' => now(),
            ]);
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $importRecord->update([
                'status' => 'failed',
                'errors' => [['row' => 0, 'column' => 'file', 'value' => '', 'error' => 'Unable to open CSV file for reading.']],
                'completed_at' => now(),
            ]);
            return;
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $importRecord->update([
                'status' => 'failed',
                'errors' => [['row' => 1, 'column' => 'header', 'value' => '', 'error' => 'Empty CSV file.']],
                'completed_at' => now(),
            ]);
            return;
        }

        // Strip BOM if present
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        $headerMap = array_map(function ($col) {
            return strtolower(trim($col));
        }, $header);

        $columnMapping = $importRecord->column_mapping ?: [];
        $duplicateHandling = $importRecord->duplicate_handling ?: 'skip';

        $totalRows = 0;
        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $failedCount = 0;
        $errors = [];

        $rowNumber = 1; // Header is row 1
        $chunkSize = 100;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            // Skip empty rows
            if (empty(array_filter($row, fn($val) => trim($val) !== ''))) {
                continue;
            }

            $totalRows++;

            // Map row data using column mapping or auto-detection
            $mappedData = [];
            foreach ($columnMapping as $dbField => $csvColumn) {
                $colIndex = is_numeric($csvColumn) ? (int)$csvColumn : array_search(strtolower(trim($csvColumn)), $headerMap);
                if ($colIndex !== false && isset($row[$colIndex])) {
                    $mappedData[$dbField] = trim($row[$colIndex]);
                }
            }

            // Fallback auto-mapping if columnMapping was empty
            if (empty($mappedData)) {
                foreach ($headerMap as $idx => $headerName) {
                    $val = isset($row[$idx]) ? trim($row[$idx]) : '';
                    if (in_array($headerName, ['name', 'client', 'client_name', 'full_name'])) {
                        $mappedData['name'] = $val;
                    } elseif (in_array($headerName, ['company', 'company_name', 'organization'])) {
                        $mappedData['company_name'] = $val;
                    } elseif (in_array($headerName, ['email', 'email_address'])) {
                        $mappedData['email'] = $val;
                    } elseif (in_array($headerName, ['phone', 'phone_number', 'mobile', 'telephone'])) {
                        $mappedData['phone'] = $val;
                    } elseif (in_array($headerName, ['client_type', 'type', 'industry', 'category'])) {
                        $mappedData['client_type'] = $val;
                    } elseif (in_array($headerName, ['status', 'client_status'])) {
                        $mappedData['status'] = $val;
                    } elseif (in_array($headerName, ['website', 'site', 'url'])) {
                        $mappedData['website'] = $val;
                    }
                }
            }

            // Validation
            $validator = Validator::make($mappedData, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:50',
                'company_name' => 'nullable|string|max:255',
                'client_type' => 'nullable|string|max:100',
                'status' => 'nullable|string|max:50',
                'website' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                $failedCount++;
                foreach ($validator->errors()->toArray() as $field => $messages) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'column' => $field,
                        'value' => $mappedData[$field] ?? '',
                        'error' => implode(', ', $messages),
                    ];
                }
                continue;
            }

            $email = strtolower($mappedData['email']);
            $existingClient = Client::where('email', $email)->first();

            $status = strtolower($mappedData['status'] ?? 'active');
            if (!in_array($status, ['active', 'inactive', 'prospect'])) {
                $status = 'active';
            }

            $clientType = !empty($mappedData['client_type']) ? ucwords(strtolower($mappedData['client_type'])) : 'Other';

            if ($existingClient) {
                if ($duplicateHandling === 'update') {
                    $existingClient->update([
                        'name' => $mappedData['name'],
                        'company' => $mappedData['company_name'] ?? $existingClient->company,
                        'company_name' => $mappedData['company_name'] ?? $existingClient->company_name,
                        'phone' => $mappedData['phone'] ?? $existingClient->phone,
                        'client_type' => $clientType,
                        'status' => $status,
                        'website' => $mappedData['website'] ?? $existingClient->website,
                    ]);
                    $updatedCount++;
                } else {
                    // Skip
                    $skippedCount++;
                }
            } else {
                // New Client
                Client::create([
                    'name' => $mappedData['name'],
                    'email' => $email,
                    'company' => $mappedData['company_name'] ?? null,
                    'company_name' => $mappedData['company_name'] ?? null,
                    'phone' => $mappedData['phone'] ?? null,
                    'client_type' => $clientType,
                    'status' => $status,
                    'website' => $mappedData['website'] ?? null,
                    'is_approved' => $status === 'active',
                    'password' => Hash::make(Str::random(24)), // Random secure password for imported client
                    'email_verified_at' => now(),
                ]);
                $importedCount++;
            }

            // Periodic progress update
            if ($totalRows % $chunkSize === 0) {
                $importRecord->update([
                    'total_rows' => $totalRows,
                    'imported' => $importedCount,
                    'updated' => $updatedCount,
                    'skipped' => $skippedCount,
                    'failed' => $failedCount,
                    'errors' => array_slice($errors, 0, 100), // Keep last 100 errors in DB
                ]);
            }
        }

        fclose($handle);

        $finalStatus = 'completed';
        if ($failedCount > 0 && ($importedCount > 0 || $updatedCount > 0)) {
            $finalStatus = 'partially_completed';
        } elseif ($failedCount > 0 && $importedCount === 0 && $updatedCount === 0) {
            $finalStatus = 'failed';
        }

        $importRecord->update([
            'status' => $finalStatus,
            'total_rows' => $totalRows,
            'imported' => $importedCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'failed' => $failedCount,
            'errors' => array_slice($errors, 0, 200),
            'completed_at' => now(),
        ]);
    }
}
