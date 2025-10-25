<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpFoundation\Response;

class StartSessionIfExists extends StartSession
{
    /**
     * Handle an incoming request.
     *
     * Only start session if:
     * - Session cookie already exists, OR
     * - Remember cookie exists, OR
     * - It's a POST/PUT/PATCH/DELETE request (mutations that need session)
     *
     * This prevents creating new sessions on GET requests without cookies.
     */
    public function handle($request, Closure $next): Response
    {
        // Always start session for mutation requests (login, register, etc.)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return parent::handle($request, $next);
        }

        // Get session cookie name
        $sessionName = $this->manager->getSessionConfig()['cookie'] ?? config('session.cookie');

        // Get remember cookie name (Laravel uses remember_web_{sha1('web')})
        $rememberCookieName = 'remember_web_' . sha1('web');

        // Check if session cookie or remember cookie exists
        $hasSessionCookie = $request->cookies->has($sessionName);
        $hasRememberCookie = $request->cookies->has($rememberCookieName);

        // If neither session nor remember cookie exists, skip session handling for GET requests
        if (!$hasSessionCookie && !$hasRememberCookie) {
            return $next($request);
        }

        // Session or remember cookie exists, proceed with normal session handling
        return parent::handle($request, $next);
    }
}

