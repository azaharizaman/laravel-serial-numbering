<?php

namespace AzahariZaman\ControlledNumber\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class RateLimitSerialGeneration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $limit = '60', string $decay = '1'): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, (int) $limit)) {
            $retryAfter = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($key, (int) $decay * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            (int) $limit,
            RateLimiter::retriesLeft($key, (int) $limit)
        );
    }

    /**
     * Resolve request signature for rate limiting.
     *
     * Note: Ensure Laravel's TrustProxies middleware is properly configured
     * when using this behind a proxy/load balancer.
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();
        $patternType = $request->input('type') ?? $request->route('type') ?? 'global';
        
        // Use getClientIp() with ip() fallback for better proxy handling
        $clientIp = $request->getClientIp() ?: $request->ip();

        if ($user) {
            return sprintf(
                'serial_generation:%s:%s:%s',
                $user->id,
                $patternType,
                $clientIp
            );
        }

        return sprintf(
            'serial_generation:%s:%s',
            $patternType,
            $clientIp
        );
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addHeaders(Response $response, int $limit, int $remaining): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $remaining),
        ]);

        return $response;
    }
}
