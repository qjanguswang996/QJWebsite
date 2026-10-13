<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessThreeDFileJob;
use App\Models\ModelFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ThreeDFileController extends Controller
{
    /**
     * Display a listing of the 3D model files.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ModelFile::class);

        $query = ModelFile::query();

        // Search by Filename
        if ($search = trim((string) $request->input('search'))) {
            $query->where('original_filename', 'like', "%{$search}%");
        }

        // Filter by Type (STL, 3MF)
        if ($type = $request->input('type')) {
            $types = is_array($type) ? $type : explode(',', (string) $type);
            $types = array_map('strtolower', array_filter($types));
            if (! empty($types)) {
                $query->whereIn('extension', $types);
            }
        }

        // Filter by Status (Completed, Processing, Failed, Pending)
        if ($status = $request->input('status')) {
            $statuses = is_array($status) ? $status : explode(',', (string) $status);
            $statuses = array_map('strtolower', array_filter($statuses));
            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        // Sorting
        $allowedSorts = [
            'created_at' => 'created_at',
            'original_filename' => 'original_filename',
            'file_size' => 'file_size',
            'status' => 'status',
        ];

        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (array_key_exists($sortBy, $allowedSorts)) {
            $query->orderBy($allowedSorts[$sortBy], $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $modelFiles = $query->paginate(20)->withQueryString();

        return view('admin.3d-files.index', [
            'modelFiles' => $modelFiles,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => (array) $request->input('type', []),
                'status' => (array) $request->input('status', []),
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

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

    /**
     * Delete a 3D model file and its physical storage file.
     */
    public function destroy(int|string $id): RedirectResponse
    {
        $modelFile = ModelFile::findOrFail($id);
        Gate::authorize('delete', $modelFile);

        $storagePath = $modelFile->storage_path;

        try {
            DB::transaction(function () use ($modelFile) {
                $modelFile->delete();
            });

            // Physical file deletion (no exception if missing)
            if ($storagePath && Storage::disk('local')->exists($storagePath)) {
                Storage::disk('local')->delete($storagePath);
            }

            return redirect()->route('admin.3d-files.index')
                ->with('success', '檔案記錄已成功刪除。');
        } catch (Throwable $e) {
            return redirect()->back()
                ->with('error', '刪除失敗：'.$e->getMessage());
        }
    }
}
