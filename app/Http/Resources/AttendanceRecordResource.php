<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => new UserResource(
                $this->whenLoaded('user')
            ),
            'date' => $this->date,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'total_break_time' => $this->total_break_time,
            'total_time' => $this->total_time,
            'comment' => $this->comment,
            'breaks' => AttendanceBreakResource::collection(
                $this->whenLoaded('breaks')
            ),
            'applications' => ApplicationResource::collection(
                $this->whenLoaded('applications')
            ),
        ];
    }
}
