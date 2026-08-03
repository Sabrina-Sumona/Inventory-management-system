<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        /** @var User|null $user */
        $user = $request->user();

        if (
            $user !== null &&
            $user->is_active === false
        ) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request
                    ->session()
                    ->invalidate();

                $request
                    ->session()
                    ->regenerateToken();
            }

            return new JsonResponse([
                'success' => false,
                'message' =>
                    'Your account has been deactivated. Please contact an administrator.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }
}