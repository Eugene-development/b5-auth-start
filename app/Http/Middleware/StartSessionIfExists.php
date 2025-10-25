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
     * Only start session if session cookie OR remember cookie already exists.
     * This prevents creating new sessions on every API request.
     */
    public function handle($request, Closure $next): Response
    {
        // Get session cookie name
        $sessionName = $this->manager->getSessionConfig()['cookie'] ?? config('session.cookie');

        // Get remember cookie name (Laravel uses remember_web_{sha1('web')})
        $rememberCookieName = 'remember_web_' . sha1('web');

        // Check if session cookie or remember cookie exists
        $hasSessionCookie = $request->cookies->has($sessionName);
        $hasRememberCookie = $request->cookies->has($rememberCookieName);

        // If neither session nor remember cookie exists, skip session handling
        if (!$hasSessionCookie && !$hasRememberCookie) {
            return $next($request);
        }

        // Session or remember cookie exists, proceed with normal session handling
        return parent::handle($request, $next);
    }
}

