<?php

namespace App\Http\Controllers\Tourist;

use App\Enums\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use App\Services\BudgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __invoke(Request $request, Trip $trip, BudgetService $budget): Response
    {
        Gate::authorize('view', $trip);

        $trip->load(['owner', 'members']);

        return Inertia::render('tourist/trips/Budget', [
            'trip' => (new TripResource($trip))->resolve(),
            'summary' => $budget->summary($trip, $budget->estimate($trip)),
            'expenses' => $trip->expenses()
                ->with(['payer:id,name', 'splits:id,expense_id,user_id,share_amount'])
                ->orderByDesc('spent_on')
                ->latest('id')
                ->get()
                ->map(fn ($expense) => [
                    'id' => $expense->id,
                    'category' => $expense->category->value,
                    'amount' => $expense->amount,
                    'spent_on' => $expense->spent_on->toDateString(),
                    'note' => $expense->note,
                    'paid_by' => $expense->paid_by,
                    'payer' => $expense->payer?->name,
                    'split_between' => $expense->splits->pluck('user_id'),
                ]),
            'travellers' => $trip->travellers()->map->only(['id', 'name'])->values(),
            'categories' => ExpenseCategory::options(),
            'can' => ['update' => Gate::allows('update', $trip)],
        ]);
    }
}
