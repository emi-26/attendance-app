<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 会員登録：名前が未入力の場合
     */
    public function test_register_requires_name(): void
    {
        $response = $this->post('/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    /**
     * 会員登録：メールアドレスが未入力の場合
     */
    public function test_register_requires_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    /**
     * 会員登録：パスワードが8文字未満の場合
     */
    public function test_register_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    /**
     * 会員登録：確認用パスワードと一致しない場合
     */
    public function test_register_password_confirmation_must_match(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    /**
     * 会員登録：パスワードが未入力の場合
     */
    public function test_register_requires_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    /**
     * 会員登録：正常にユーザー情報が保存される
     */
    public function test_user_can_register(): void
    {
        $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'admin_status' => false,
        ]);
    }

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