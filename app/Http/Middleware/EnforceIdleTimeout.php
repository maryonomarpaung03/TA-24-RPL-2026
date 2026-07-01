<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleTimeout
{
    /**
     * Logout otomatis bila tidak ada aktivitas selama batas waktu (menit).
     * Batas diambil dari config('session.lifetime') → SESSION_LIFETIME.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timeoutSeconds = max(1, (int) config('session.lifetime', 10)) * 60;
            $now = time();
            $lastActivity = $request->session()->get('last_activity_at');

            if ($lastActivity !== null && ($now - (int) $lastActivity) > $timeoutSeconds) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $minutes = intdiv($timeoutSeconds, 60);

                return redirect()->route('login')->with(
                    'error',
                    "Anda telah keluar otomatis karena tidak ada aktivitas selama {$minutes} menit."
                );
            }

            $request->session()->put('last_activity_at', $now);
        }

        return $next($request);
    }
}
