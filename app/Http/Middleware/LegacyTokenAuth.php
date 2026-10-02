<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Support\Facades\DB;

class LegacyTokenAuth
{
    public function handle($request, Closure $next)
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization'));

        // Legacy auth: token stored directly in users table and compared as plain text.
        if (!$token) {
            return ApiResponse::error('UNAUTHENTICATED', 'Token not found.', 401);
        }

        $user = DB::table('users')->where('api_token', $token)->first();

        if (!$user) {
            return ApiResponse::error('UNAUTHENTICATED', 'Invalid token.', 401);
        }

        $request->merge(['auth_user_id' => $user->id]);
        return $next($request);
    }
}
