<?php

namespace App\Http\Controllers\Tourist;

use App\Enums\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Trip;
use App\Services\BudgetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function __construct(private BudgetService $budget) {}

    public function store(Request $request, Trip $trip): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $this->validated($request, $trip);

        if (isset($validated['client_uuid']) && $trip->expenses()->where('client_uuid', $validated['client_uuid'])->exists()) {
            return back();
        }

        $this->budget->record($trip, $request->user(), collect($validated)->except('split_between')->all(), $validated['split_between'] ?? []);

        Inertia::flash('toast', $this->toast($trip));

        return back();
    }

    public function update(Request $request, Trip $trip, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $validated = $this->validated($request, $trip);

        DB::transaction(function () use ($expense, $validated) {
            $expense->update(collect($validated)->except(['split_between', 'client_uuid'])->all());
            $this->budget->split($expense, $validated['split_between'] ?? []);
        });

        $this->budget->checkThresholds($trip);

        return back();
    }

    public function destroy(Trip $trip, Expense $expense): RedirectResponse
    {
        Gate::authorize('update', $trip);

        $expense->delete();
        $this->budget->checkThresholds($trip);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Trip $trip): array
    {
        $travellerIds = $trip->loadMissing(['owner', 'members'])->travellers()->pluck('id')->all();

        return $request->validate([
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'spent_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'paid_by' => ['nullable', 'integer', Rule::in($travellerIds)],
            'split_between' => ['nullable', 'array'],
            'split_between.*' => ['integer', Rule::in($travellerIds)],
            'client_uuid' => ['nullable', 'uuid'],
        ]);
    }

    /**
     * @return array{type: string, message: string}
     */
    private function toast(Trip $trip): array
    {
        $trip->refresh();

        return match (true) {
            $trip->budget_alert_level >= 100 => ['type' => 'warning', 'message' => __('Expense saved. You are now over your budget.')],
            $trip->budget_alert_level >= 80 => ['type' => 'warning', 'message' => __('Expense saved. You have used over 80% of your budget.')],
            default => ['type' => 'success', 'message' => __('Expense saved.')],
        };
    }
}
