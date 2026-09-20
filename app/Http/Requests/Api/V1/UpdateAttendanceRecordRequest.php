<?php

namespace App\Http\Requests\Api\V1;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAttendanceRecordRequest extends FormRequest
{
    /**
     * リクエストの実行を許可する。
     *
     * @return bool 常にtrue
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 勤怠更新時のバリデーションルールを返す。
     *
     * @return array<string, mixed> バリデーションルール
     */
    public function rules(): array
    {
        $attendanceRecord = $this->route('attendanceRecord');

        return [
            'date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                Rule::unique('attendance_records', 'date')
                    ->where(
                        'user_id',
                        $attendanceRecord->user_id
                    )
                    ->ignore($attendanceRecord->id),
            ],
            'clock_in' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
            ],
            'clock_out' => [
                'sometimes',
                'nullable',
                'date_format:H:i:s',
            ],
            'comment' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * 出退勤時刻の前後関係を追加で検証する。
     *
     * @param  Validator  $validator  バリデーター
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                ! $this->has('clock_in')
                && ! $this->has('clock_out')
            ) {
                return;
            }

            if (
                $validator->errors()->has('clock_in')
                || $validator->errors()->has('clock_out')
            ) {
                return;
            }

            $attendanceRecord = $this->route('attendanceRecord');

            $clockInValue = $this->input(
                'clock_in',
                $attendanceRecord->clock_in
            );

            $clockOutValue = $this->has('clock_out')
                ? $this->input('clock_out')
                : $attendanceRecord->clock_out;

            if ($clockOutValue === null) {
                return;
            }

            $clockIn = Carbon::createFromFormat(
                'H:i:s',
                $clockInValue
            );

            $clockOut = Carbon::createFromFormat(
                'H:i:s',
                $clockOutValue
            );

            if ($clockOut->gt($clockIn)) {
                return;
            }

            $validator->errors()->add(
                'clock_out',
                '退勤時刻は出勤時刻より後の時刻を指定してください。'
            );
        });
    }

    /**
     * 勤怠更新時のエラーメッセージを返す。
     *
     * @return array<string, string> エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',
            'date.unique' => 'この日付の勤怠は既に登録されています。',
            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',
            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',
            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}
