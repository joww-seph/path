<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('tourist/Dashboard', [
            'checklist' => [
                'preferences' => $user->touristProfile()->exists(),
                'emergencyContacts' => $user->emergencyContacts()->exists(),
                'phoneVerified' => $user->phone_verified_at !== null,
            ],
        ]);
    }
}
