<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(
            function (
                NotFoundHttpException $exception,
                $request
            ): ?JsonResponse {
                if (! $request->is('api/*')) {
                    return null;
                }

                return response()->json([
                    'error' => '勤怠情報が見つかりませんでした。',
                ], 404);
            }
        );

        $this->renderable(
            function (
                AccessDeniedHttpException $exception,
                $request
            ): ?JsonResponse {
                if (! $request->is('api/*')) {
                    return null;
                }

                return response()->json([
                    'error' => 'この操作を実行する権限がありません。',
                ], 403);
            }
        );
    }
}
