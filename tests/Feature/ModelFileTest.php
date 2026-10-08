<?php

namespace Tests\Feature;

use App\Models\ModelFile;
use App\Models\User;
use App\Policies\ModelFilePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModelFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_files_table_has_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('model_files'));

        $expectedColumns = [
            'id',
            'original_filename',
            'stored_filename',
            'storage_path',
            'extension',
            'mime_type',
            'file_size',
            'status',
            'vertex_count',
            'triangle_count',
            'volume',
            'surface_area',
            'width',
            'height',
            'depth',
            'center_x',
            'center_y',
            'center_z',
            'min_x',
            'min_y',
            'min_z',
            'max_x',
            'max_y',
            'max_z',
            'metadata',
            'error_message',
            'parsed_at',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('model_files', $column),
                "Table 'model_files' is missing column: {$column}"
            );
        }
    }

    public function test_model_files_table_has_indexes(): void
    {
        $indexes = Schema::getIndexes('model_files');
        $indexColumns = collect($indexes)->flatMap(fn ($index) => $index['columns'])->unique()->toArray();

        $this->assertContains('status', $indexColumns);
        $this->assertContains('extension', $indexColumns);
        $this->assertContains('created_at', $indexColumns);
    }

    public function test_can_create_model_file_using_factory_with_default_pending_state(): void
    {
        $modelFile = ModelFile::factory()->create();

        $this->assertDatabaseHas('model_files', [
            'id' => $modelFile->id,
            'status' => 'pending',
            'extension' => 'stl',
        ]);

        $this->assertEquals(ModelFile::STATUS_PENDING, $modelFile->status);
        $this->assertNull($modelFile->vertex_count);
        $this->assertNull($modelFile->triangle_count);
        $this->assertNull($modelFile->metadata);
        $this->assertNull($modelFile->parsed_at);
    }

    public function test_factory_processing_completed_and_failed_states(): void
    {
        $processing = ModelFile::factory()->processing()->create();
        $this->assertEquals(ModelFile::STATUS_PROCESSING, $processing->status);

        $completed = ModelFile::factory()->completed()->create();
        $this->assertEquals(ModelFile::STATUS_COMPLETED, $completed->status);
        $this->assertIsArray($completed->metadata);
        $this->assertEquals('mm', $completed->metadata['units']);
        $this->assertNotNull($completed->parsed_at);
        $this->assertIsInt($completed->vertex_count);
        $this->assertIsFloat($completed->volume);

        $failed = ModelFile::factory()->failed()->create();
        $this->assertEquals(ModelFile::STATUS_FAILED, $failed->status);
        $this->assertNotNull($failed->error_message);
    }

    public function test_attribute_casts_work_as_expected(): void
    {
        $modelFile = ModelFile::create([
            'original_filename' => 'test_cube.stl',
            'stored_filename' => 'abc123.stl',
            'storage_path' => 'models/abc123.stl',
            'extension' => 'stl',
            'mime_type' => 'model/stl',
            'file_size' => '10240',
            'status' => ModelFile::STATUS_COMPLETED,
            'vertex_count' => '36',
            'triangle_count' => '12',
            'volume' => '1000.5',
            'surface_area' => '600.25',
            'width' => '10.0',
            'height' => '10.0',
            'depth' => '10.0',
            'center_x' => '0.0',
            'center_y' => '0.0',
            'center_z' => '0.0',
            'min_x' => '-5.0',
            'min_y' => '-5.0',
            'min_z' => '-5.0',
            'max_x' => '5.0',
            'max_y' => '5.0',
            'max_z' => '5.0',
            'metadata' => ['bounding_box_calculated' => true, 'layers' => 200],
            'parsed_at' => '2026-10-08 10:00:00',
        ]);

        $fresh = $modelFile->fresh();

        $this->assertIsInt($fresh->file_size);
        $this->assertEquals(10240, $fresh->file_size);

        $this->assertIsInt($fresh->vertex_count);
        $this->assertEquals(36, $fresh->vertex_count);

        $this->assertIsInt($fresh->triangle_count);
        $this->assertEquals(12, $fresh->triangle_count);

        $this->assertIsFloat($fresh->volume);
        $this->assertEquals(1000.5, $fresh->volume);

        $this->assertIsFloat($fresh->surface_area);
        $this->assertEquals(600.25, $fresh->surface_area);

        $this->assertIsFloat($fresh->width);
        $this->assertIsFloat($fresh->height);
        $this->assertIsFloat($fresh->depth);

        $this->assertIsFloat($fresh->center_x);
        $this->assertIsFloat($fresh->min_x);
        $this->assertIsFloat($fresh->max_x);

        $this->assertIsArray($fresh->metadata);
        $this->assertTrue($fresh->metadata['bounding_box_calculated']);
        $this->assertEquals(200, $fresh->metadata['layers']);

        $this->assertInstanceOf(Carbon::class, $fresh->parsed_at);
        $this->assertEquals('2026-10-08 10:00:00', $fresh->parsed_at->format('Y-m-d H:i:s'));
    }

    public function test_status_constants_are_defined_correctly(): void
    {
        $this->assertEquals('pending', ModelFile::STATUS_PENDING);
        $this->assertEquals('processing', ModelFile::STATUS_PROCESSING);
        $this->assertEquals('completed', ModelFile::STATUS_COMPLETED);
        $this->assertEquals('failed', ModelFile::STATUS_FAILED);

        $this->assertEquals(
            ['pending', 'processing', 'completed', 'failed'],
            ModelFile::STATUSES
        );
    }

    public function test_policy_allows_actions(): void
    {
        $policy = new ModelFilePolicy();
        $modelFile = ModelFile::factory()->make();

        $this->assertTrue($policy->viewAny(null));
        $this->assertTrue($policy->view(null, $modelFile));
        $this->assertTrue($policy->create(null));
        $this->assertTrue($policy->update(null, $modelFile));
        $this->assertTrue($policy->delete(null, $modelFile));
        $this->assertTrue($policy->restore(null, $modelFile));
        $this->assertTrue($policy->forceDelete(null, $modelFile));
    }
}
