<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:100']]);

        return Inertia::render('admin/ActivityLog', [
            'entries' => ActivityLog::with('user:id,name,email')
                ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', 'like', "{$action}%"))
                ->latest('id')
                ->paginate(30)
                ->withQueryString(),
            'filters' => (object) $filters,
        ]);
    }
}
