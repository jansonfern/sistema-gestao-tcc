<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ConfigurableThrottle
{
    public function handle(Request $request, Closure $next, string $type = 'auth'): Response
    {
        $key = 'throttle:'.$type.'|'.$request->ip();
        $maxAttempts = (int) config('app.rate_limits.'.$type, 30);
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $message = 'Muitas tentativas. Aguarde um minuto e tente novamente.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => $message], 429);
            }

            return back()->with('error', $message)->withInput();
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
