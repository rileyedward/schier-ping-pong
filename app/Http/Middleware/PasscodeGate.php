<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasscodeGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('unlock') || $request->is('unlock/*')) {
            return $next($request);
        }

        if ($request->session()->get('passcode_verified')) {
            return $next($request);
        }

        return redirect()->route('unlock');
    }
}
