<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'break_in',
        'break_out',
    ];

    /**
     * 所属する修正申請を取得する。
     *
     * @return BelongsTo 修正申請とのリレーション
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
