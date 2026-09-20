<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordApiValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_requires_date_and_clock_in(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/attendance-records',
            []
        );

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'date',
                'clock_in',
            ])
            ->assertJsonPath(
                'errors.date.0',
                '勤怠日は必須です。'
            )
            ->assertJsonPath(
                'errors.clock_in.0',
                '出勤時刻は必須です。'
            );
    }
}
