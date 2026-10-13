<?php

namespace Tests\Feature;

use App\Models\ModelFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThreeDFileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

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

    public function test_can_download_original_3d_file_via_download_route(): void
    {
        Storage::fake('local');

        $filePath = '3d-files/sample_download.stl';
        Storage::disk('local')->put($filePath, 'solid sample_download endsolid sample_download');

        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'sample_download.stl',
            'stored_filename' => 'sample_download.stl',
            'storage_path' => $filePath,
            'extension' => 'stl',
            'status' => ModelFile::STATUS_COMPLETED,
        ]);

        $response = $this->get(route('admin.3d-files.download', $modelFile->id));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=sample_download.stl');
        $this->assertEquals('solid sample_download endsolid sample_download', $response->streamedContent());
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

    public function test_show_page_displays_sections_and_formats_values(): void
    {
        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'gear_model.stl',
            'extension' => 'stl',
            'mime_type' => 'model/stl',
            'file_size' => 1024,
            'status' => ModelFile::STATUS_COMPLETED,
            'vertex_count' => 300,
            'triangle_count' => 100,
            'volume' => 12.345,
            'surface_area' => 45.678,
            'width' => 10.0,
            'height' => 20.0,
            'depth' => 30.0,
            'min_x' => -5.0,
            'min_y' => -10.0,
            'min_z' => -15.0,
            'max_x' => 5.0,
            'max_y' => 10.0,
            'max_z' => 15.0,
            'center_x' => 0.0,
            'center_y' => 0.0,
            'center_z' => 0.0,
            'parsed_at' => now(),
        ]);

        $response = $this->get(route('admin.3d-files.show', $modelFile->id));

        $response->assertStatus(200);
        $response->assertSee('gear_model.stl');
        $response->assertSee('Model Information');
        $response->assertSee('Geometry Parameters');
        $response->assertSee('Bounding Box');
        $response->assertSee('Center Coordinates');
        $response->assertSee('File Information');
        $response->assertSee('10.00 mm');
        $response->assertSee('20.00 mm');
        $response->assertSee('30.00 mm');
        $response->assertSee('12.345 cm³');
        $response->assertSee('45.678 cm²');
        $response->assertSee('300');
        $response->assertSee('100');
        $response->assertSee(route('admin.3d-files.download', $modelFile->id));
    }

    public function test_show_page_handles_null_values_with_dash_fallback(): void
    {
        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'empty_metrics.stl',
            'extension' => 'stl',
            'status' => ModelFile::STATUS_PENDING,
            'vertex_count' => null,
            'triangle_count' => null,
            'volume' => null,
            'surface_area' => null,
            'width' => null,
            'height' => null,
            'depth' => null,
            'min_x' => null,
            'min_y' => null,
            'min_z' => null,
            'max_x' => null,
            'max_y' => null,
            'max_z' => null,
            'center_x' => null,
            'center_y' => null,
            'center_z' => null,
            'parsed_at' => null,
        ]);

        $response = $this->get(route('admin.3d-files.show', $modelFile->id));

        $response->assertStatus(200);
        $response->assertSee('檔案解析處理中...');
        $response->assertSee('-');
    }

    public function test_show_page_displays_error_state_on_failed_status(): void
    {
        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'failed_model.stl',
            'extension' => 'stl',
            'status' => ModelFile::STATUS_FAILED,
            'error_message' => 'Corrupted mesh structure',
        ]);

        $response = $this->get(route('admin.3d-files.show', $modelFile->id));

        $response->assertStatus(200);
        $response->assertSee('模型解析失敗');
        $response->assertSee('Corrupted mesh structure');
    }
}
