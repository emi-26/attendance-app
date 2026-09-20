<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 管理者ログイン：メールアドレスが未入力の場合
     */
    public function test_admin_login_requires_email(): void
    {
        User::factory()->create([
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 管理者ログイン：パスワードが未入力の場合
     */
    public function test_admin_login_requires_password(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 管理者ログイン：登録内容と一致しない場合
     */
    public function test_admin_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
            'admin_status' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * 管理者は管理者画面にアクセスできる
     */
    public function test_admin_can_access_admin_page(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list');

        $response->assertStatus(200);
    }
}
