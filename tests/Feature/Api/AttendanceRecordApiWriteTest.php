<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordApiWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_record(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.date', '2026-09-10');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'date' => '2026-09-10',
        ]);
    }

    public function test_unauthenticated_user_cannot_create_record(): void
    {
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
        ]);

        $response->assertStatus(401);
    }

    public function test_owner_can_update_record(): void
    {
        $user = User::factory()->create();
        $record = $this->createRecord($user);

        Sanctum::actingAs($user);

        $response = $this->putJson(
            '/api/v1/attendance-records/'.$record->id,
            [
                'clock_in' => '08:30:00',
                'clock_out' => '17:30:00',
                'comment' => '変更後',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.comment', '変更後');

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'comment' => '変更後',
        ]);
    }

    public function test_other_user_cannot_update_record(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = $this->createRecord($owner);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson(
            '/api/v1/attendance-records/'.$record->id,
            [
                'comment' => '変更後',
            ]
        );

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);
    }

    public function test_admin_can_update_other_users_record(): void
    {
        $owner = User::factory()->create();

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $record = $this->createRecord($owner);

        Sanctum::actingAs($admin);

        $response = $this->putJson(
            '/api/v1/attendance-records/'.$record->id,
            [
                'comment' => '管理者変更',
            ]
        );

        $response->assertStatus(200)
            ->assertJsonPath('data.comment', '管理者変更');
    }

    public function test_owner_can_delete_record(): void
    {
        $user = User::factory()->create();
        $record = $this->createRecord($user);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/'.$record->id
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $record->id,
        ]);
    }

    public function test_other_user_cannot_delete_record(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $record = $this->createRecord($owner);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/'.$record->id
        );

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'この操作を実行する権限がありません。',
            ]);
    }

    private function createRecord(User $user): AttendanceRecord
    {
        return AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '変更前',
        ]);
    }
}
