<?php

namespace App\Http\Middleware;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Closure;

class PermissionGate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (auth()->check()) {
            if ($request->user()->status != 1) {
                session()->flush();
                abort(403, 'Your access to app has been blocked! Please contact the administrator');
            }

            if ($request->user()->can($permission)) {
                return $next($request);
            } else {
                if ($permission) {
                    $canGoThrough = false;

                    foreach (auth()->user()->roles as $role) {
                        if ($role->permissions->where('name', $permission)->count()) {
                            $canGoThrough = true;
                            break;
                        }
                    }

                    if ($canGoThrough) { 
                        return $next($request);
                    }
                }

                abort(403);
            }
        } else {
            abort(403);
        }
    }
}
