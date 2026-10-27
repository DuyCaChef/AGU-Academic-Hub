<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $userRole = $user->vai_tro?->value ?? (string) $user->vai_tro;

        // Nếu người dùng có một trong các vai trò được cấp phép
        if (in_array($userRole, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Bạn không có thẩm quyền truy cập khu vực này.');
    }
}
