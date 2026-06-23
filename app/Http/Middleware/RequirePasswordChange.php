<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password) {
            $profileUrl = route('filament.admin.auth.profile');

            if ($request->url() !== $profileUrl) {
                $request->session()->put('url.intended_after_password_change', $request->url());

                return redirect($profileUrl);
            }
        }

        return $next($request);
    }
}
