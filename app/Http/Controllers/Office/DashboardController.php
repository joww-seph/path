<?php

namespace App\Http\Controllers\Office;

use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Business;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('office/Dashboard', [
            'stats' => [
                'pendingPartners' => Business::where('verification_status', VerificationStatus::Pending)->count(),
                'approvedPartners' => Business::approved()->count(),
            ],
        ]);
    }
}
