<?php
// app/Http/Middleware/IsAdmin.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdmin {
    public function handle(Request $request, Closure $next) {
        if (!Auth::check()) {
            return redirect()->route('admin.login')
                             ->withErrors(['acceso' => 'Debes iniciar sesión primero.']);
        }

        if (!Auth::user()->isAdmin()) {
            Auth::logout();
            return redirect()->route('admin.login')
                             ->withErrors(['acceso' => 'No tienes permisos de administrador.']);
        }

        return $next($request);
    }
}
