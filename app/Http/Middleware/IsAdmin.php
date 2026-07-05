<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Cek apakah pengguna sudah login
        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $userRole = auth()->user()->role;

        // 2. Jika tidak ada parameter role spesifik yang dikirim dari route, 
        // izinkan siapa saja asal bukan 'customer'
        if (empty($roles)) {
            if ($userRole === 'customer') {
                return response()->json(['message' => 'Forbidden. Akses ditolak.'], 403);
            }
            return $next($request);
        }

        // 3. Jika route meminta role spesifik (contoh: hanya 'admin' atau 'warehouse')
        if (!in_array($userRole, $roles)) {
            return response()->json(['message' => 'Forbidden. Anda tidak memiliki hak akses untuk modul ini.'], 403);
        }

        return $next($request);
    }
}
