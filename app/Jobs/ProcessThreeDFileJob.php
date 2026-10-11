<?php

namespace App\Jobs;

use App\Models\ModelFile;
use App\Services\ThreeD\StlParserService;
use App\Services\ThreeD\ThreeDParserInterface;
use App\Services\ThreeD\ThreeMfParserService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessThreeDFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ModelFile $modelFile
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh model state from DB to check current status
        $this->modelFile->refresh();

        // Prevent duplicate processing if file is already completed
        if ($this->modelFile->status === ModelFile::STATUS_COMPLETED) {
            return;
        }

        // Prevent duplicate concurrent execution if file is already processing on first attempt
        if ($this->modelFile->status === ModelFile::STATUS_PROCESSING && $this->attempts() <= 1) {
            return;
        }

        // Set status to processing when job starts
        $this->modelFile->update([
            'status' => ModelFile::STATUS_PROCESSING,
            'error_message' => null,
        ]);

        try {
            $extension = strtolower($this->modelFile->extension);
            $parser = $this->resolveParser($extension);

            $filePath = $this->getAbsoluteFilePath($this->modelFile->storage_path);

            $result = $parser->parse($filePath);

            if (($result['status'] ?? null) === ModelFile::STATUS_COMPLETED) {
                $this->modelFile->update([
                    'status' => ModelFile::STATUS_COMPLETED,
                    'vertex_count' => $result['vertex_count'] ?? 0,
                    'triangle_count' => $result['triangle_count'] ?? 0,
                    'volume' => $result['volume'] ?? 0.0,
                    'surface_area' => $result['surface_area'] ?? 0.0,
                    'width' => $result['width'] ?? 0.0,
                    'height' => $result['height'] ?? 0.0,
                    'depth' => $result['depth'] ?? 0.0,
                    'center_x' => $result['center']['x'] ?? 0.0,
                    'center_y' => $result['center']['y'] ?? 0.0,
                    'center_z' => $result['center']['z'] ?? 0.0,
                    'min_x' => $result['bounds']['min']['x'] ?? 0.0,
                    'min_y' => $result['bounds']['min']['y'] ?? 0.0,
                    'min_z' => $result['bounds']['min']['z'] ?? 0.0,
                    'max_x' => $result['bounds']['max']['x'] ?? 0.0,
                    'max_y' => $result['bounds']['max']['y'] ?? 0.0,
                    'max_z' => $result['bounds']['max']['z'] ?? 0.0,
                    'metadata' => $result['metadata'] ?? [],
                    'error_message' => null,
                    'parsed_at' => Carbon::now(),
                ]);
            } else {
                $errorMessage = $result['error_message'] ?? 'Failed to parse 3D file.';
                $this->modelFile->update([
                    'status' => ModelFile::STATUS_FAILED,
                    'error_message' => $errorMessage,
                ]);

                throw new Exception($errorMessage);
            }
        } catch (Throwable $e) {
            $this->modelFile->update([
                'status' => ModelFile::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve parser instance based on file extension.
     */
    protected function resolveParser(string $extension): ThreeDParserInterface
    {
        return match ($extension) {
            'stl' => app(StlParserService::class),
            '3mf' => app(ThreeMfParserService::class),
            default => throw new Exception("Unsupported 3D file format [{$extension}]."),
        };
    }

    /**
     * Resolve absolute file path from storage path.
     */
    protected function getAbsoluteFilePath(string $storagePath): string
    {
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        if (Storage::disk('local')->exists($storagePath)) {
            return Storage::disk('local')->path($storagePath);
        }

        if (Storage::exists($storagePath)) {
            return Storage::path($storagePath);
        }

        return storage_path('app/'.ltrim($storagePath, '/'));
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception = null): void
    {
        if ($this->modelFile->exists) {
            $this->modelFile->update([
                'status' => ModelFile::STATUS_FAILED,
                'error_message' => $exception ? $exception->getMessage() : 'Job processing failed after max attempts.',
            ]);
        }
    }
}
