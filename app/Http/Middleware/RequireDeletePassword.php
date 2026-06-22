<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class RequireDeletePassword
{
    public function handle(Request $request, Closure $next): Response
    {
        $password = (string) $request->input('password', '');

        if ($password === '' || !Hash::check($password, Auth::user()->password)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Incorrect password. Deletion cancelled.'], 422);
            }
            return back()->withErrors(['password' => 'Incorrect password. Deletion cancelled.']);
        }

        return $next($request);
    }
}
