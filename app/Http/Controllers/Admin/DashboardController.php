<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $counts = User::query()->toBase()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return Inertia::render('admin/Dashboard', [
            'usersByRole' => collect(Role::cases())->map(fn (Role $role) => [
                'role' => $role->value,
                'label' => $role->label(),
                'total' => (int) ($counts[$role->value] ?? 0),
            ]),
            'deactivatedUsers' => User::whereNotNull('deactivated_at')->count(),
            'recentActivity' => ActivityLog::with('user:id,name')->latest('id')->limit(8)->get(),
        ]);
    }
}
