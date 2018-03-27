<?php

namespace App\Http\Middleware;

use Closure;

class AccessSupervisor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!in_array($request->session()->get('user_status'), ['supervisor', 'admin', 'super-admin']))
        {
            return redirect('/');
        }

        return $next($request);
    }
}
