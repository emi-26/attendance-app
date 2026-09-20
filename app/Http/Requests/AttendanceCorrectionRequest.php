<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionRequest extends FormRequest
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
     * 勤怠修正時のバリデーションルールを返す。
     *
     * @return array<string, mixed> バリデーションルール
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => ['required', 'date_format:H:i'],
            'new_clock_out' => ['required', 'date_format:H:i'],
            'new_break_in' => ['nullable', 'array'],
            'new_break_in.*' => ['nullable', 'date_format:H:i'],
            'new_break_out' => ['nullable', 'array'],
            'new_break_out.*' => ['nullable', 'date_format:H:i'],
            'comment' => ['required', 'string'],
        ];
    }

    /**
     * 勤怠修正時のエラーメッセージを返す。
     *
     * @return array<string, string> エラーメッセージ
     */
    public function messages(): array
    {
        return [
            'comment.required' => '備考を記入してください',
        ];
    }

    /**
     * 出退勤時刻と休憩時刻の前後関係を追加検証する。
     *
     * @return array<int, callable> 追加バリデーション処理
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    $validator->errors()->has('new_clock_in') ||
                    $validator->errors()->has('new_clock_out')
                ) {
                    return;
                }

                $clockIn = Carbon::createFromFormat(
                    'H:i',
                    $this->input('new_clock_in')
                );

                $clockOut = Carbon::createFromFormat(
                    'H:i',
                    $this->input('new_clock_out')
                );

                if ($clockIn->gt($clockOut)) {
                    $message = $this->user()->admin_status
                        ? '出勤時間もしくは退勤時間が不適切な値です'
                        : '出勤時間が不適切な値です';

                    $validator->errors()->add(
                        'new_clock_in',
                        $message
                    );

                    return;
                }

                $breakIns = $this->input('new_break_in', []);
                $breakOuts = $this->input('new_break_out', []);

                foreach ($breakIns as $index => $breakInValue) {
                    if (empty($breakInValue)) {
                        continue;
                    }

                    $breakIn = Carbon::createFromFormat(
                        'H:i',
                        $breakInValue
                    );

                    if (
                        $breakIn->lt($clockIn) ||
                        $breakIn->gt($clockOut)
                    ) {
                        $validator->errors()->add(
                            'new_break_in.'.$index,
                            '休憩時間が不適切な値です'
                        );
                    }
                }

                foreach ($breakOuts as $index => $breakOutValue) {
                    if (empty($breakOutValue)) {
                        continue;
                    }

                    $breakOut = Carbon::createFromFormat(
                        'H:i',
                        $breakOutValue
                    );

                    if ($breakOut->gt($clockOut)) {
                        $validator->errors()->add(
                            'new_break_out.'.$index,
                            '休憩時間もしくは退勤時間が不適切な値です'
                        );
                    }
                }
            },
        ];
    }
}
