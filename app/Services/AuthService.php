<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function login(string $email, string $password): ?array
    {
        $user = DB::table('users')->where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password)) return null;

        $token = Str::random(60);
        DB::table('users')->where('id', $user->id)->update(['api_token' => $token]);
        return ['token' => $token, 'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]];
    }

    public function user(int $id): ?object { return DB::table('users')->where('id', $id)->first(); }

    public function logout(int $id): void { DB::table('users')->where('id', $id)->update(['api_token' => null]); }
}
