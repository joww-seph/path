<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\BudgetThresholdReached;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Trip spending: totals, budget alerts, shared costs and who owes whom.
 */
class BudgetService
{
    /**
     * Alert the travellers when spending reaches these percentages of the budget.
     */
    public const THRESHOLDS = [80, 100];

    /**
     * Record an expense and split it between the chosen travellers.
     *
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $splitBetween
     */
    public function record(Trip $trip, User $author, array $attributes, array $splitBetween): Expense
    {
        $expense = DB::transaction(function () use ($trip, $author, $attributes, $splitBetween) {
            $expense = new Expense($attributes);
            $expense->trip_id = $trip->id;
            $expense->user_id = $author->id;
            $expense->paid_by ??= $author->id;
            $expense->save();

            $this->split($expense, $splitBetween);

            return $expense;
        });

        $this->checkThresholds($trip);

        return $expense;
    }

    /**
     * Share an expense evenly. Any centavos left over go to the first people listed.
     *
     * @param  list<int>  $userIds
     */
    public function split(Expense $expense, array $userIds): void
    {
        $expense->splits()->delete();

        $userIds = array_values(array_unique($userIds));

        if ($userIds === []) {
            return;
        }

        $cents = (int) round((float) $expense->amount * 100);
        $base = intdiv($cents, count($userIds));
        $remainder = $cents % count($userIds);

        foreach ($userIds as $index => $userId) {
            $expense->splits()->create([
                'user_id' => $userId,
                'share_amount' => ($base + ($index < $remainder ? 1 : 0)) / 100,
            ]);
        }
    }

    /**
     * Notify the travellers the first time spending passes 80% and 100% of the budget.
     * If spending drops back (an expense is deleted or the budget raised), the alert can fire again.
     */
    public function checkThresholds(Trip $trip): void
    {
        if ($trip->budget === null || (float) $trip->budget <= 0) {
            return;
        }

        $percent = $this->spent($trip) / (float) $trip->budget * 100;
        $reached = collect(self::THRESHOLDS)->filter(fn (int $threshold) => $percent >= $threshold)->max() ?? 0;

        if ($reached > $trip->budget_alert_level) {
            Notification::send(
                $trip->loadMissing(['owner', 'members'])->travellers(),
                new BudgetThresholdReached($trip, $reached, $this->spent($trip)),
            );
        }

        if ($reached !== $trip->budget_alert_level) {
            $trip->forceFill(['budget_alert_level' => $reached])->save();
        }
    }

    /**
     * A rough cost of the plan: entrance fees and starting prices for every traveller.
     */
    public function estimate(Trip $trip): float
    {
        $trip->loadMissing('items.listing');

        return (float) $trip->items->sum(
            fn ($item) => (float) ($item->listing?->entrance_fee ?? $item->listing?->price_min ?? 0) * $trip->pax,
        );
    }

    public function spent(Trip $trip): float
    {
        return (float) $trip->expenses()->sum('amount');
    }

    /**
     * Everything the budget page shows about a trip's money.
     *
     * @return array<string, mixed>
     */
    public function summary(Trip $trip, float $estimated): array
    {
        $trip->loadMissing(['owner', 'members']);
        $expenses = $trip->expenses()->with(['splits', 'payer:id,name'])->get();
        $spent = (float) $expenses->sum('amount');
        $budget = $trip->budget !== null ? (float) $trip->budget : null;

        return [
            'spent' => round($spent, 2),
            'budget' => $budget,
            'estimated' => round($estimated, 2),
            'remaining' => $budget !== null ? round($budget - $spent, 2) : null,
            'percent' => $budget ? round($spent / $budget * 100, 1) : null,
            'by_category' => collect(ExpenseCategory::cases())->map(fn (ExpenseCategory $category) => [
                'category' => $category->value,
                'label' => $category->label(),
                'total' => round((float) $expenses->where('category', $category)->sum('amount'), 2),
            ])->values(),
            'by_day' => $expenses->groupBy(fn (Expense $expense) => $expense->spent_on->toDateString())
                ->map(fn (Collection $day, string $date) => ['date' => $date, 'total' => round((float) $day->sum('amount'), 2)])
                ->sortKeys()
                ->values(),
            'balances' => $this->balances($trip, $expenses),
            'settlements' => $this->settlements($this->balances($trip, $expenses)),
        ];
    }

    /**
     * What each traveller paid, what their shares add up to, and the difference.
     *
     * @param  Collection<int, Expense>  $expenses
     * @return list<array{user_id: int, name: string, paid: float, share: float, net: float}>
     */
    public function balances(Trip $trip, Collection $expenses): array
    {
        $people = $trip->travellers();

        $paid = $expenses->groupBy('paid_by')->map(fn (Collection $items) => (float) $items->sum('amount'));
        $shares = $expenses->flatMap->splits->groupBy('user_id')->map(fn (Collection $items) => (float) $items->sum('share_amount'));

        return $people->map(fn (User $person) => [
            'user_id' => $person->id,
            'name' => $person->name,
            'paid' => round($paid[$person->id] ?? 0, 2),
            'share' => round($shares[$person->id] ?? 0, 2),
            'net' => round(($paid[$person->id] ?? 0) - ($shares[$person->id] ?? 0), 2),
        ])->values()->all();
    }

    /**
     * The fewest payments that square everyone up: the biggest debtor pays the biggest creditor first.
     *
     * @param  list<array{user_id: int, name: string, net: float}>  $balances
     * @return list<array{from: string, from_id: int, to: string, to_id: int, amount: float}>
     */
    public function settlements(array $balances): array
    {
        $creditors = collect($balances)->filter(fn ($row) => $row['net'] > 0.004)->sortByDesc('net')->values()->all();
        $debtors = collect($balances)->filter(fn ($row) => $row['net'] < -0.004)->sortBy('net')->values()->all();
        $payments = [];

        $i = 0;
        $j = 0;

        while ($i < count($debtors) && $j < count($creditors)) {
            $amount = min(-$debtors[$i]['net'], $creditors[$j]['net']);

            $payments[] = [
                'from' => $debtors[$i]['name'],
                'from_id' => $debtors[$i]['user_id'],
                'to' => $creditors[$j]['name'],
                'to_id' => $creditors[$j]['user_id'],
                'amount' => round($amount, 2),
            ];

            $debtors[$i]['net'] += $amount;
            $creditors[$j]['net'] -= $amount;

            if (abs($debtors[$i]['net']) < 0.005) {
                $i++;
            }

            if (abs($creditors[$j]['net']) < 0.005) {
                $j++;
            }
        }

        return $payments;
    }
}
