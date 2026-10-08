<?php

namespace Tests\Feature\Admin;

use App\Models\ParsedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_can_be_accessed(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('3D Analytics');
        $response->assertSee('儀表板');
        $response->assertSee('總解析數量');
        $response->assertSee('最近解析檔案');
        $response->assertSee('系統狀態');
    }

    public function test_admin_dashboard_shows_empty_state_when_no_files_exist(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('尚無 3D 解析檔案');
    }

    public function test_admin_dashboard_displays_parsed_files_when_present(): void
    {
        ParsedFile::create([
            'filename' => 'gear_test.stl',
            'original_name' => 'gear_test.stl',
            'format' => 'STL',
            'file_size' => 1048576,
            'status' => 'completed',
            'parsed_at' => now(),
        ]);

        $response = $this->get('/admin');

        $response->assertStatus(200);
        $response->assertSee('gear_test.stl');
        $response->assertSee('1.00 MB');
        $response->assertSee('STL');
    }

    public function test_admin_dashboard_supports_demo_states(): void
    {
        $responseLoading = $this->get('/admin?demo_state=loading');
        $responseLoading->assertSee('Loading State');

        $responseError = $this->get('/admin?demo_state=error');
        $responseError->assertSee('Error State');
        $responseError->assertSee('無法載入 3D 數據儀表板');
    }
}
