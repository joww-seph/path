<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The privacy notice required by the Data Privacy Act of 2012 (RA 10173).
 */
class PrivacyController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('guide/Privacy', [
            'contactEmail' => config('app.privacy_email'),
        ]);
    }
}
