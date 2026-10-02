<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use App\Exceptions\InsufficientStockException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register()
    {
        $this->renderable(function (InsufficientStockException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('INSUFFICIENT_STOCK', $exception->getMessage(), 409, [
                    'available' => $exception->available,
                    'requested' => $exception->requested,
                ]);
            }
        });

        $this->renderable(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error('VALIDATION_FAILED', 'The given data was invalid.', 422, $exception->errors());
            }
        });

        $this->renderable(function (Throwable $exception, Request $request) {
            if ($request->is('api/*') && $this->shouldReturnJson($request, $exception)) {
                $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;
                $status = $status >= 400 && $status < 600 ? $status : 500;
                $code = $status === 404 ? 'NOT_FOUND' : ($status === 401 ? 'UNAUTHENTICATED' : 'INTERNAL_ERROR');
                return ApiResponse::error($code, $status === 404 ? 'Resource not found.' : ($status === 401 ? 'Unauthenticated.' : 'An unexpected error occurred.'), $status);
            }
        });
    }
}
