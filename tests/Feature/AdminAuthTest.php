<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('後台管理系統');
    }

    public function test_user_can_login_with_username_and_valid_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'admin1234',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    public function test_user_can_login_with_email_and_valid_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->post('/admin/login', [
            'username' => 'admin@example.com',
            'password' => 'admin1234',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/admin/login');
    }

    public function test_user_can_logout_and_cannot_access_admin_afterwards(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();

        $dashboardResponse = $this->get('/admin');
        $dashboardResponse->assertRedirect('/admin/login');
    }
}
