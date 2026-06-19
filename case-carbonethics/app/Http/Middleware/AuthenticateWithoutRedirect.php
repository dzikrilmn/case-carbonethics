<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class AuthenticateWithoutRedirect extends Middleware
{
    /**
     * Handle an unauthenticated user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->is('api/*')) {
            return $this->abortWithJson();
        }

        parent::unauthenticated($request, $guards);
    }

    protected function abortWithJson()
    {
        abort(response()->json([
            'message' => 'Unauthenticated.',
        ], 401));
    }
}
