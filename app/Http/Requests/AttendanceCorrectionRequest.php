<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceCorrectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

    public function messages(): array
    {
        return [
            'comment.required' => '備考を記入してください',
        ];
    }

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

                    if ($breakIn->lt($clockIn) || $breakIn->gt($clockOut)) {
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
