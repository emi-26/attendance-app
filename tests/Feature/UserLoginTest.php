<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 一般ログイン：メールアドレスが未入力の場合
     */
    public function test_user_login_requires_email(): void
    {
        User::factory()->create([
            'password' => 'password',
            'admin_status' => false,
        ]);

        $response = $this->post('/login', [
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 一般ログイン：パスワードが未入力の場合
     */
    public function test_user_login_requires_password(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 一般ログイン：登録内容と一致しない場合
     */
    public function test_user_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password',
            'admin_status' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    /**
     * 一般ユーザーは管理者画面にアクセスできない
     */
    public function test_general_user_cannot_access_admin_page(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/attendance/list');

        $response->assertStatus(403);
    }
}
