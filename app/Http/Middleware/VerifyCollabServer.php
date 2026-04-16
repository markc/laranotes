<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCollabServer
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header('X-Collab-Secret');

        if (! $secret || ! hash_equals(config('collab.secret', ''), $secret)) {
            abort(403, 'Invalid collab server credentials.');
        }

        return $next($request);
    }
}
