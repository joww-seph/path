<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * The languages PaTH is translated into.
     */
    public const SUPPORTED = ['en' => 'English', 'fil' => 'Filipino'];

    /**
     * Use the visitor's chosen language: their account setting, then their session.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale');

        if (is_string($locale) && array_key_exists($locale, self::SUPPORTED)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
