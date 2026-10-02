<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $service)
    {
        $result = $service->login($request->validated('email'), $request->validated('password'));
        return $result
            ? ApiResponse::success($result)
            : ApiResponse::error('INVALID_CREDENTIALS', 'Invalid credentials.', 401);
    }

    public function me(Request $request, AuthService $service)
    {
        return ApiResponse::success((new UserResource($service->user((int) $request->auth_user_id)))->resolve($request));
    }

    public function logout(Request $request, AuthService $service)
    {
        $service->logout((int) $request->auth_user_id);
        return ApiResponse::success(['logged_out' => true]);
    }
}
