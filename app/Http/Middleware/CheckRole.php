<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (! Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        if (! in_array($user->role, ['admin', 'medico', 'paciente'])) {
            abort(403, 'Role de usuário inválido.');
        }

        if (count($roles) === 1 && str_contains($roles[0], ',')) {
            $roles = explode(',', $roles[0]);
            $roles = array_map('trim', $roles);
        }

        foreach ($roles as $role) {
            if ($user->role === $role) {
                return $next($request);
            }
        }

        abort(403, 'Acesso negado. Você não tem permissão para acessar esta página.');
    }
}
