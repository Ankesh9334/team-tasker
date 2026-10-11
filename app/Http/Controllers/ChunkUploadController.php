<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ChunkUploadController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');
        $fileName = $request->input('fileName');
        $fileId = $request->input('fileId');
        $mimeType = $request->input('mimeType');

        $tempDir = storage_path('app/chunks/'.$fileId);
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0777, true);
        }

        $file->move($tempDir, $chunkIndex);

        if (($chunkIndex + 1) == $totalChunks) {
            $finalName = time().'_'.preg_replace('/[^A-Za-z0-9\-\_\.]/', '', $fileName);
            $finalPath = storage_path('app/public/chat-attachments/'.$finalName);
            if (! File::exists(dirname($finalPath))) {
                File::makeDirectory(dirname($finalPath), 0777, true);
            }

            $out = fopen($finalPath, 'wb');
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir.'/'.$i;
                if (File::exists($chunkPath)) {
                    $in = fopen($chunkPath, 'rb');
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    fclose($in);
                    unlink($chunkPath);
                }
            }
            fclose($out);
            rmdir($tempDir);

            return response()->json([
                'status' => 'completed',
                'path' => 'chat-attachments/'.$finalName,
                'original_name' => $fileName,
                'mime_type' => $mimeType ?: 'application/octet-stream',
                'size' => filesize($finalPath),
                'url' => asset('storage/chat-attachments/'.$finalName),
            ]);
        }

        return response()->json(['status' => 'chunk_uploaded', 'progress' => round(($chunkIndex + 1) / $totalChunks * 100)]);
    }
}
