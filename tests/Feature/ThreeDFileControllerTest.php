<?php

namespace Tests\Feature;

use App\Models\ModelFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThreeDFileControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_download_or_stream_stored_3d_file(): void
    {
        Storage::fake('local');

        $filePath = '3d-files/sample.stl';
        Storage::disk('local')->put($filePath, 'solid test_stl endsolid test_stl');

        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'sample.stl',
            'stored_filename' => 'sample.stl',
            'storage_path' => $filePath,
            'extension' => 'stl',
            'status' => ModelFile::STATUS_COMPLETED,
        ]);

        $response = $this->get(route('admin.3d-files.file', $modelFile->id));

        $response->assertStatus(200);
        $this->assertEquals('solid test_stl endsolid test_stl', $response->streamedContent());
    }

    public function test_file_endpoint_returns_404_if_file_missing(): void
    {
        Storage::fake('local');

        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'missing.stl',
            'stored_filename' => 'missing.stl',
            'storage_path' => '3d-files/missing.stl',
            'extension' => 'stl',
            'status' => ModelFile::STATUS_PENDING,
        ]);

        $response = $this->get(route('admin.3d-files.file', $modelFile->id));

        $response->assertStatus(404);
    }
}
