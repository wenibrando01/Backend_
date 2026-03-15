<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== 'student') {
            return response()->json(['message' => 'Unauthorized. Student access required.'], 403);
        }
        return $next($request);
    }
}
