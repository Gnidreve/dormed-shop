<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceTestMode
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'app.test_mode' => true,
            'paypal.mode' => 'sandbox',
        ]);

        return $next($request);
    }
}
