<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessThreeDFileJob;
use App\Models\ModelFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThreeDFileController extends Controller
{
    /**
     * Show the detail page for a 3D model file.
     */
    public function show(int|string $id): View
    {
        $modelFile = ModelFile::findOrFail($id);

        $processingTime = null;
        if ($modelFile->parsed_at && $modelFile->created_at) {
            $seconds = $modelFile->created_at->diffInSeconds($modelFile->parsed_at);
            if ($seconds < 60) {
                $processingTime = "{$seconds} 秒";
            } else {
                $minutes = round($seconds / 60, 2);
                $processingTime = "{$minutes} 分鐘";
            }
        }

        return view('admin.3d-files.show', [
            'modelFile' => $modelFile,
            'processingTime' => $processingTime,
        ]);
    }

    /**
     * Handle 3D model file upload and dispatch processing job.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:102400'],
        ]);

        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize();

        $path = $file->store('3d-files', 'local');
        $storedFilename = basename($path);

        $modelFile = ModelFile::create([
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'storage_path' => $path,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'status' => ModelFile::STATUS_PENDING,
        ]);

        ProcessThreeDFileJob::dispatch($modelFile);

        return redirect()->route('admin.3d-files.show', $modelFile->id)
            ->with('success', '檔案上傳成功，已加入解析佇列。');
    }
}
