<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Send each user to the home page of their own area.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return to_route($request->user()->homeRoute());
    }
}
