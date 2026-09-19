<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsRevisor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_revisor) {
            return $next($request);
        }

        return redirect()
            ->route('homepage')
            ->with('errorMessage', __('ui.revisorOnly'));
    }
}
