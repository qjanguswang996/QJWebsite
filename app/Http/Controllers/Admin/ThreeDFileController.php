<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessThreeDFileJob;
use App\Models\ModelFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ThreeDFileController extends Controller
{
    /**
     * Show the detail page for a 3D model file.
     */
    public function show(int|string $id): View
    {
        $modelFile = ModelFile::findOrFail($id);
        Gate::authorize('view', $modelFile);

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

    /**
     * Serve the stored 3D model file for rendering in the viewer.
     */
    public function file(int|string $id): StreamedResponse
    {
        $modelFile = ModelFile::findOrFail($id);
        Gate::authorize('view', $modelFile);

        if (! Storage::disk('local')->exists($modelFile->storage_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('local')->response(
            $modelFile->storage_path,
            $modelFile->original_filename,
            [
                'Content-Type' => $modelFile->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.rawurlencode($modelFile->original_filename).'"',
            ]
        );
    }

    /**
     * Download the original 3D model file securely.
     */
    public function download(int|string $id): StreamedResponse
    {
        $modelFile = ModelFile::findOrFail($id);
        Gate::authorize('view', $modelFile);

        if (! Storage::disk('local')->exists($modelFile->storage_path)) {
            abort(404, 'File not found on storage.');
        }

        return Storage::disk('local')->download(
            $modelFile->storage_path,
            $modelFile->original_filename
        );
    }
}
