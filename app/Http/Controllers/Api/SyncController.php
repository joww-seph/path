<?php

namespace App\Http\Controllers\Api;

use App\Enums\ExpenseCategory;
use App\Http\Controllers\Controller;
use App\Models\ItineraryItem;
use App\Models\Trip;
use App\Services\BudgetService;
use App\Services\ItineraryPlanner;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Applies changes a tourist made while offline, in the order they made them.
 *
 * Each operation carries the time it was made on the phone. Expenses are de-duplicated by their
 * client_uuid; stop changes use last-write-wins, so an edit older than the server copy is skipped.
 */
class SyncController extends Controller
{
    public const MAX_OPERATIONS = 200;

    public function __construct(
        private BudgetService $budget,
        private ItineraryPlanner $planner,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'operations' => ['required', 'array', 'max:'.self::MAX_OPERATIONS],
            'operations.*.id' => ['required', 'string', 'max:64'],
            'operations.*.type' => ['required', Rule::in(['expense.create', 'item.update'])],
            'operations.*.trip_id' => ['required', 'integer'],
            'operations.*.made_at' => ['required', 'date'],
            'operations.*.data' => ['required', 'array'],
        ]);

        $results = [];
        $touchedTrips = [];

        foreach ($request->input('operations') as $operation) {
            $trip = Trip::find($operation['trip_id']);

            if ($trip === null || Gate::denies('update', $trip)) {
                $results[] = $this->result($operation, 'rejected', 'You cannot change this trip.');

                continue;
            }

            [$status, $message] = match ($operation['type']) {
                'expense.create' => $this->createExpense($request, $trip, $operation),
                'item.update' => $this->updateItem($trip, $operation),
            };

            if ($status === 'applied') {
                $touchedTrips[$trip->id] = $trip;
            }

            $results[] = $this->result($operation, $status, $message);
        }

        foreach ($touchedTrips as $trip) {
            $this->planner->schedule($trip);
        }

        return response()->json(['results' => $results]);
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{0: string, 1: string|null}
     */
    private function createExpense(Request $request, Trip $trip, array $operation): array
    {
        $validator = Validator::make($operation['data'], [
            'client_uuid' => ['required', 'uuid'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'spent_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ['rejected', $validator->errors()->first()];
        }

        $data = $validator->validated();

        if ($trip->expenses()->where('client_uuid', $data['client_uuid'])->exists()) {
            return ['duplicate', null];
        }

        $everyone = $trip->loadMissing(['owner', 'members'])->travellers()->pluck('id')->all();
        $this->budget->record($trip, $request->user(), $data, $everyone);

        return ['applied', null];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{0: string, 1: string|null}
     */
    private function updateItem(Trip $trip, array $operation): array
    {
        $validator = Validator::make($operation['data'], [
            'item_id' => ['required', 'integer'],
            'is_done' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return ['rejected', $validator->errors()->first()];
        }

        $data = $validator->validated();
        $item = $trip->items()->whereKey($data['item_id'])->first();

        if (! $item instanceof ItineraryItem) {
            return ['rejected', 'That stop is no longer on the trip.'];
        }

        if ($item->updated_at !== null && $item->updated_at->greaterThan(CarbonImmutable::parse($operation['made_at']))) {
            return ['conflict', 'Someone changed this stop more recently. Their change was kept.'];
        }

        $item->update(collect($data)->except('item_id')->all());

        return ['applied', null];
    }

    /**
     * @param  array<string, mixed>  $operation
     * @return array{id: string, status: string, message: string|null}
     */
    private function result(array $operation, string $status, ?string $message = null): array
    {
        return ['id' => $operation['id'], 'status' => $status, 'message' => $message];
    }
}
