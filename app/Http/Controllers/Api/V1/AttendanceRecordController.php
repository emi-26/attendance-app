<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠記録一覧を条件付きで取得する。
     *
     * @param  IndexAttendanceRecordRequest  $request  検索条件を含むリクエスト
     * @return AnonymousResourceCollection 勤怠記録一覧
     */
    public function index(
        IndexAttendanceRecordRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 20;

        $query = AttendanceRecord::with([
            'user',
            'breaks',
        ]);

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['date'])) {
            $query->whereDate('date', $validated['date']);
        }

        if (isset($validated['month'])) {
            $query->whereYear(
                'date',
                substr($validated['month'], 0, 4)
            )->whereMonth(
                'date',
                substr($validated['month'], 5, 2)
            );
        }

        $attendanceRecords = $query
            ->latest('date')
            ->paginate($perPage);

        return AttendanceRecordResource::collection(
            $attendanceRecords
        );
    }

    /**
     * 指定した勤怠記録の詳細を取得する。
     *
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠記録
     * @return AttendanceRecordResource 勤怠記録詳細
     */
    public function show(
        AttendanceRecord $attendanceRecord
    ): AttendanceRecordResource {
        $attendanceRecord->load([
            'user',
            'breaks',
            'applications',
        ]);

        return new AttendanceRecordResource(
            $attendanceRecord
        );
    }

    /**
     * 認証ユーザーの勤怠記録を新規登録する。
     *
     * @param  StoreAttendanceRecordRequest  $request  検証済みの勤怠情報
     * @return JsonResponse 登録した勤怠記録
     */
    public function store(
        StoreAttendanceRecordRequest $request
    ): JsonResponse {
        $attendanceRecord = $request->user()
            ->attendanceRecords()
            ->create($request->validated());

        $attendanceRecord->load([
            'user',
            'breaks',
        ]);

        return (new AttendanceRecordResource(
            $attendanceRecord
        ))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 権限確認後に指定した勤怠記録を更新する。
     *
     * @param  UpdateAttendanceRecordRequest  $request  検証済みの更新内容
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠記録
     * @return AttendanceRecordResource 更新後の勤怠記録
     */
    public function update(
        UpdateAttendanceRecordRequest $request,
        AttendanceRecord $attendanceRecord
    ): AttendanceRecordResource {
        $this->authorize(
            'update',
            $attendanceRecord
        );

        $attendanceRecord->update(
            $request->validated()
        );

        $attendanceRecord->load([
            'user',
            'breaks',
        ]);

        return new AttendanceRecordResource(
            $attendanceRecord
        );
    }

    /**
     * 権限確認後に指定した勤怠記録を削除する。
     *
     * @param  AttendanceRecord  $attendanceRecord  対象の勤怠記録
     * @return Response 空の204レスポンス
     */
    public function destroy(
        AttendanceRecord $attendanceRecord
    ): Response {
        $this->authorize(
            'delete',
            $attendanceRecord
        );

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
