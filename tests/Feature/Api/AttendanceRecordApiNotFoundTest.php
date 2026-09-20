<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordApiNotFoundTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_missing_attendance_record_returns_not_found(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson(
            '/api/v1/attendance-records/99999',
            [
                'comment' => '変更後',
            ]
        );

        $response->assertStatus(404);
    }

    public function test_delete_missing_attendance_record_returns_not_found(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/99999'
        );

        $response->assertStatus(404);
    }
}
