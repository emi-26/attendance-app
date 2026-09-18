<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function getTotalBreakTimeAttribute(): string
    {
        $breakMinutes = $this->breaks
            ->filter(
                fn (AttendanceBreak $break): bool => $break->break_in !== null &&
                    $break->break_out !== null
            )
            ->sum(
                fn (AttendanceBreak $break): int => Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    )
            );

        return $this->formatMinutes($breakMinutes);
    }

    public function getTotalTimeAttribute(): string
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return '';
        }

        $workMinutes = Carbon::parse($this->clock_in)
            ->diffInMinutes(
                Carbon::parse($this->clock_out)
            );

        $breakMinutes = $this->breaks
            ->filter(
                fn (AttendanceBreak $break): bool => $break->break_in !== null &&
                    $break->break_out !== null
            )
            ->sum(
                fn (AttendanceBreak $break): int => Carbon::parse($break->break_in)
                    ->diffInMinutes(
                        Carbon::parse($break->break_out)
                    )
            );

        return $this->formatMinutes(
            $workMinutes - $breakMinutes
        );
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf(
            '%02d:%02d',
            intdiv($minutes, 60),
            $minutes % 60
        );
    }
}
