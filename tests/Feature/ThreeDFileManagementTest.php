<?php

namespace Tests\Feature;

use App\Models\ModelFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThreeDFileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_can_view_3d_file_list_with_pagination(): void
    {
        ModelFile::factory()->count(25)->create();

        $response = $this->get(route('admin.3d-files.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.3d-files.index');
        $response->assertSee('3D 檔案管理列表');

        // Assert pagination (20 per page)
        $modelFiles = $response->viewData('modelFiles');
        $this->assertEquals(20, $modelFiles->count());
        $this->assertEquals(25, $modelFiles->total());
    }

    public function test_can_search_3d_files_by_filename(): void
    {
        ModelFile::factory()->create(['original_filename' => 'gear_part.stl']);
        ModelFile::factory()->create(['original_filename' => 'bracket.3mf']);

        $response = $this->get(route('admin.3d-files.index', ['search' => 'gear']));

        $response->assertStatus(200);
        $response->assertSee('gear_part.stl');
        $response->assertDontSee('bracket.3mf');
    }

    public function test_can_filter_3d_files_by_type(): void
    {
        ModelFile::factory()->create(['original_filename' => 'gear.stl', 'extension' => 'stl']);
        ModelFile::factory()->create(['original_filename' => 'cube.3mf', 'extension' => '3mf']);

        $response = $this->get(route('admin.3d-files.index', ['type' => ['3mf']]));

        $response->assertStatus(200);
        $response->assertSee('cube.3mf');
        $response->assertDontSee('gear.stl');
    }

    public function test_can_filter_3d_files_by_status(): void
    {
        ModelFile::factory()->create(['original_filename' => 'done.stl', 'status' => ModelFile::STATUS_COMPLETED]);
        ModelFile::factory()->create(['original_filename' => 'working.stl', 'status' => ModelFile::STATUS_PROCESSING]);
        ModelFile::factory()->create(['original_filename' => 'broken.stl', 'status' => ModelFile::STATUS_FAILED]);

        $response = $this->get(route('admin.3d-files.index', ['status' => ['failed']]));

        $response->assertStatus(200);
        $response->assertSee('broken.stl');
        $response->assertDontSee('done.stl');
        $response->assertDontSee('working.stl');
    }

    public function test_can_sort_3d_files(): void
    {
        ModelFile::factory()->create(['original_filename' => 'AAA.stl', 'file_size' => 5000]);
        ModelFile::factory()->create(['original_filename' => 'ZZZ.stl', 'file_size' => 1000]);

        $response = $this->get(route('admin.3d-files.index', [
            'sort_by' => 'original_filename',
            'sort_dir' => 'asc',
        ]));

        $response->assertStatus(200);
        $files = $response->viewData('modelFiles');
        $this->assertEquals('AAA.stl', $files->first()->original_filename);
    }

    public function test_can_delete_3d_file_record_and_physical_file(): void
    {
        Storage::fake('local');

        $path = '3d-files/test_delete.stl';
        Storage::disk('local')->put($path, 'dummy content');

        $file = ModelFile::factory()->create([
            'original_filename' => 'test_delete.stl',
            'storage_path' => $path,
        ]);

        $this->assertDatabaseHas('model_files', ['id' => $file->id]);
        Storage::disk('local')->assertExists($path);

        $response = $this->delete(route('admin.3d-files.destroy', $file->id));

        $response->assertRedirect(route('admin.3d-files.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('model_files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_missing_physical_file_does_not_throw_exception(): void
    {
        Storage::fake('local');

        $file = ModelFile::factory()->create([
            'original_filename' => 'missing_physical.stl',
            'storage_path' => '3d-files/missing_physical.stl',
        ]);

        // Ensure physical file does NOT exist
        Storage::disk('local')->assertMissing($file->storage_path);

        $response = $this->delete(route('admin.3d-files.destroy', $file->id));

        $response->assertRedirect(route('admin.3d-files.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('model_files', ['id' => $file->id]);
    }
}
