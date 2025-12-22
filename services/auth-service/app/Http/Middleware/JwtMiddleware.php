<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            
            // Attach user to request
            $request->merge(['auth_user' => $user]);
            
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token invalid or expired'], 401);
        }
        
        return $next($request);
    }
}
