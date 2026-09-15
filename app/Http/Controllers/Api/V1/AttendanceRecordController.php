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
    public function index(
        IndexAttendanceRecordRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 20;

        $query = AttendanceRecord::with('user');

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
