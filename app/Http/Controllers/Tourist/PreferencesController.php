<?php

namespace App\Http\Controllers\Tourist;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tourist\UpdatePreferencesRequest;
use App\Models\TouristProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreferencesController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('tourist/Preferences', [
            'profile' => $request->user()->touristProfile,
            'interestOptions' => TouristProfile::INTERESTS,
            'accessibilityOptions' => TouristProfile::ACCESSIBILITY_NEEDS,
        ]);
    }

    public function update(UpdatePreferencesRequest $request): RedirectResponse
    {
        $request->user()->touristProfile()->updateOrCreate([], $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Travel preferences saved.')]);

        return to_route('tourist.preferences.edit');
    }
}
