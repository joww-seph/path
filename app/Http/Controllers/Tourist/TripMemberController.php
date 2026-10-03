<?php

namespace App\Http\Controllers\Tourist;

use App\Enums\Role;
use App\Enums\TripRole;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Travel companions who can view or edit a trip.
 */
class TripMemberController extends Controller
{
    public function store(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('manage', $trip);

        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in([TripRole::Editor->value, TripRole::Viewer->value])],
        ]);

        $companion = User::where('email', Str::lower($validated['email']))->first();

        if ($companion === null || $companion->role !== Role::Tourist) {
            throw ValidationException::withMessages([
                'email' => __('No tourist account uses that email. Ask your companion to sign up for PaTH first.'),
            ]);
        }

        if ($companion->id === $trip->user_id) {
            throw ValidationException::withMessages(['email' => __('That is you. You already own this trip.')]);
        }

        $trip->members()->syncWithoutDetaching([$companion->id => ['role' => $validated['role']]]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name can now see this trip.', ['name' => $companion->name])]);

        return back();
    }

    public function update(Request $request, Trip $trip, User $member): RedirectResponse
    {
        Gate::authorize('manage', $trip);

        $validated = $request->validate([
            'role' => ['required', Rule::in([TripRole::Editor->value, TripRole::Viewer->value])],
        ]);

        $trip->members()->updateExistingPivot($member->id, ['role' => $validated['role']]);

        return back();
    }

    /**
     * The owner removes a companion, or a companion leaves the trip.
     */
    public function destroy(Request $request, Trip $trip, User $member): RedirectResponse
    {
        abort_unless($request->user()->id === $member->id || Gate::allows('manage', $trip), 403);

        $trip->members()->detach($member->id);

        return $request->user()->id === $member->id ? to_route('tourist.trips.index') : back();
    }
}
