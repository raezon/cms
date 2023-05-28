<?php

namespace App\Http\Middleware;

use App\Model\DeliveryMan;
use Closure;
use Illuminate\Support\Facades\Auth;


class ApiActiveDeliveryMan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $dm = DeliveryMan::firstWhere('auth_token', $request->token);

        if (isset($dm) && $dm->is_active == 1) {
            return $next($request);
        }
        $errors = [];
        $errors[] = ['code' => 'auth-001', 'message' => 'Delivery man is inactive!'];
        return response()->json([
            'errors' => $errors
        ], 401);
    }
}
