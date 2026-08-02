<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiToken;
use Illuminate\Http\Request;

class ApiTokenMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        $role = null
    ) {

        // ambil token dari header Authorization
        $token = $request->bearerToken();


        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan'
            ], 401);
        }


        // cari token yang tersimpan di database
        $apiToken = ApiToken::with('user')
            ->where(
                'token',
                hash('sha256', $token)
            )
            ->first();


        if (!$apiToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }


        // ambil user pemilik token
        $user = $apiToken->user;


        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 401);
        }


        // cek role user
        if ($role && $user->role !== $role) {

            return response()->json([
                'success' => false,
                'message' => 'Akses hanya untuk '.$role
            ], 403);

        }


        $request->attributes->set('auth_user', $user);
// dd([
//     'token_user' => $user,
//     'role' => $user->role
// ]);

        return $next($request);
    }
}