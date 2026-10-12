<?php

namespace Tests\Feature;

use App\Jobs\ProcessThreeDFileJob;
use App\Models\ModelFile;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessThreeDFileJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function createValidBinaryStlContent(): string
    {
        $header = str_pad('Test Binary STL File', 80, "\x00");
        $triangleCount = pack('V', 1);

        $facet = pack('g3g3g3g3v',
            0.0, 0.0, 1.0,
            0.0, 0.0, 0.0,
            10.0, 0.0, 0.0,
            0.0, 10.0, 0.0,
            0
        );

        return $header.$triangleCount.$facet;
    }

    public function test_uploading_3d_file_dispatches_process_job_to_queue(): void
    {
        Queue::fake();

        $fileContent = $this->createValidBinaryStlContent();
        $uploadedFile = UploadedFile::fake()->createWithContent('cube.stl', $fileContent);

        $response = $this->post(route('admin.3d-files.upload'), [
            'file' => $uploadedFile,
        ]);

        $modelFile = ModelFile::latest('id')->first();
        $this->assertNotNull($modelFile);
        $this->assertEquals('cube.stl', $modelFile->original_filename);
        $this->assertEquals('pending', $modelFile->status);

        $response->assertRedirect(route('admin.3d-files.show', $modelFile->id));

        Queue::assertPushed(ProcessThreeDFileJob::class, function (ProcessThreeDFileJob $job) use ($modelFile) {
            return $job->modelFile->id === $modelFile->id;
        });
    }

    public function test_job_has_expected_tries_and_timeout_configurations(): void
    {
        $modelFile = ModelFile::factory()->create();
        $job = new ProcessThreeDFileJob($modelFile);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals(120, $job->timeout);
    }

    public function test_job_successfully_parses_3d_file_and_saves_results_to_database(): void
    {
        $fileContent = $this->createValidBinaryStlContent();
        Storage::disk('local')->put('3d-files/test_cube.stl', $fileContent);

        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'test_cube.stl',
            'stored_filename' => 'test_cube.stl',
            'storage_path' => '3d-files/test_cube.stl',
            'extension' => 'stl',
            'status' => ModelFile::STATUS_PENDING,
        ]);

        $job = new ProcessThreeDFileJob($modelFile);
        $job->handle();

        $modelFile->refresh();

        $this->assertEquals(ModelFile::STATUS_COMPLETED, $modelFile->status);
        $this->assertNull($modelFile->error_message);
        $this->assertNotNull($modelFile->parsed_at);
        $this->assertEquals(3, $modelFile->vertex_count);
        $this->assertEquals(1, $modelFile->triangle_count);
        $this->assertEquals(10.0, $modelFile->width);
        $this->assertEquals(10.0, $modelFile->height);
        $this->assertEquals(0.0, $modelFile->depth);
        $this->assertGreaterThan(0, $modelFile->surface_area);
    }

    public function test_job_sets_status_to_failed_and_persists_error_message_on_parser_failure(): void
    {
        Storage::disk('local')->put('3d-files/corrupted.stl', 'invalid stl content');

        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'corrupted.stl',
            'stored_filename' => 'corrupted.stl',
            'storage_path' => '3d-files/corrupted.stl',
            'extension' => 'stl',
            'status' => ModelFile::STATUS_PENDING,
        ]);

        $job = new ProcessThreeDFileJob($modelFile);

        try {
            $job->handle();
            $this->fail('Expected Exception was not thrown.');
        } catch (Exception $e) {
            // Expected exception
        }

        $modelFile->refresh();

        $this->assertEquals(ModelFile::STATUS_FAILED, $modelFile->status);
        $this->assertNotNull($modelFile->error_message);
    }

    public function test_job_failed_callback_records_error_message(): void
    {
        $modelFile = ModelFile::factory()->create([
            'status' => ModelFile::STATUS_PENDING,
        ]);

        $job = new ProcessThreeDFileJob($modelFile);
        $exception = new Exception('Worker fatal memory exhausted');

        $job->failed($exception);

        $modelFile->refresh();

        $this->assertEquals(ModelFile::STATUS_FAILED, $modelFile->status);
        $this->assertEquals('Worker fatal memory exhausted', $modelFile->error_message);
    }

    public function test_job_prevents_duplicate_parsing_for_completed_file(): void
    {
        $fileContent = $this->createValidBinaryStlContent();
        Storage::disk('local')->put('3d-files/cube.stl', $fileContent);

        $initialParsedAt = now()->subHours(2);

        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'cube.stl',
            'storage_path' => '3d-files/cube.stl',
            'extension' => 'stl',
            'status' => ModelFile::STATUS_COMPLETED,
            'parsed_at' => $initialParsedAt,
            'vertex_count' => 999,
        ]);

        $job = new ProcessThreeDFileJob($modelFile);
        $job->handle();

        $modelFile->refresh();

        $this->assertEquals(999, $modelFile->vertex_count);
        $this->assertEquals($initialParsedAt->toDateTimeString(), $modelFile->parsed_at->toDateTimeString());
    }

    public function test_admin_detail_page_displays_all_required_file_metrics(): void
    {
        $modelFile = ModelFile::factory()->create([
            'original_filename' => 'gear_component.stl',
            'stored_filename' => 'random_gear_123.stl',
            'extension' => 'stl',
            'mime_type' => 'model/stl',
            'file_size' => 204800,
            'status' => ModelFile::STATUS_COMPLETED,
            'vertex_count' => 1500,
            'triangle_count' => 500,
            'volume' => 12.345,
            'surface_area' => 45.678,
            'width' => 25.0,
            'height' => 30.0,
            'depth' => 15.0,
            'parsed_at' => now(),
        ]);

        $response = $this->get(route('admin.3d-files.show', $modelFile->id));

        $response->assertStatus(200);
        $response->assertSee('gear_component.stl');
        $response->assertSee('stl');
        $response->assertSee('Completed');
        $response->assertSee('204,800 Bytes');
        $response->assertSee('25.00 mm');
        $response->assertSee('30.00 mm');
        $response->assertSee('15.00 mm');
        $response->assertSee('1,500');
        $response->assertSee('500');
        $response->assertSee('12.345 cm³');
        $response->assertSee('45.678 cm²');
    }
}
