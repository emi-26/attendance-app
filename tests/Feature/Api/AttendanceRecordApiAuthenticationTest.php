<?php

namespace Tests\Feature\Api;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_update_record(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '変更前',
        ]);

        $response = $this->putJson(
            '/api/v1/attendance-records/'.$record->id,
            [
                'comment' => '変更後',
            ]
        );

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
            'comment' => '変更前',
        ]);
    }

    public function test_unauthenticated_user_cannot_delete_record(): void
    {
        $user = User::factory()->create();

        $record = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '変更前',
        ]);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/'.$record->id
        );

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $record->id,
        ]);
    }
}
