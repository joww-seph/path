<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(Role::class)],
            'status' => ['nullable', Rule::in(['active', 'deactivated'])],
        ]);

        $users = User::query()
            ->search($filters['search'] ?? null)
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->where('role', $role))
            ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->whereNull('deactivated_at'))
            ->when(($filters['status'] ?? null) === 'deactivated', fn ($query) => $query->whereNotNull('deactivated_at'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'email_verified_at' => $user->email_verified_at,
                'deactivated_at' => $user->deactivated_at,
                'created_at' => $user->created_at,
            ]);

        return Inertia::render('admin/Users', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::options(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);

        $this->ensureNotSelf($request, $user);

        $previous = $user->role;
        $user->role = Role::from($validated['role']);
        $user->save();

        ActivityLog::record('user.role_changed', $user, ['from' => $previous->value, 'to' => $user->role->value]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name is now a :role.', ['name' => $user->name, 'role' => $user->role->label()])]);

        return back();
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        $this->ensureNotSelf($request, $user);

        $user->forceFill(['deactivated_at' => now()])->save();

        ActivityLog::record('user.deactivated', $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been deactivated.', ['name' => $user->name])]);

        return back();
    }

    public function reactivate(User $user): RedirectResponse
    {
        $user->forceFill(['deactivated_at' => null])->save();

        ActivityLog::record('user.reactivated', $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':name has been reactivated.', ['name' => $user->name])]);

        return back();
    }

    /**
     * Stop administrators from locking themselves out.
     */
    private function ensureNotSelf(Request $request, User $user): void
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['user' => __('You cannot change your own account here.')]);
        }
    }
}
