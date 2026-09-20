<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * 修正申請をAPIレスポンス用の配列に変換する。
     *
     * @param  Request  $request  APIリクエスト
     * @return array<string, mixed> 修正申請データ
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'attendance_record_id' => $this->attendance_record_id,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
            'comment' => $this->comment,
            'status' => $this->status,
        ];
    }
}
