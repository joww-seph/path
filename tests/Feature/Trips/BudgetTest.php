<?php

namespace Tests\Feature\Trips;

use App\Models\Expense;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\BudgetThresholdReached;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_traveller_logs_an_expense_split_evenly_between_companions(): void
    {
        $trip = Trip::factory()->create(['budget' => null]);
        $friend = User::factory()->create();
        $trip->members()->attach($friend, ['role' => 'editor']);

        $this->actingAs($trip->owner)->post(route('tourist.trips.expenses.store', $trip), [
            'category' => 'food',
            'amount' => 100,
            'spent_on' => $trip->start_date->toDateString(),
            'note' => 'Empanada',
            'split_between' => [$trip->owner->id, $friend->id, $trip->owner->id],
        ])->assertSessionHasNoErrors();

        $expense = $trip->expenses()->sole();
        $this->assertSame($trip->owner->id, $expense->paid_by);
        $this->assertEqualsCanonicalizing(['50.00', '50.00'], $expense->splits()->pluck('share_amount')->all());
    }

    public function test_leftover_centavos_go_to_the_first_people(): void
    {
        $trip = Trip::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $expense = Expense::factory()->for($trip)->create(['amount' => 100]);

        app(BudgetService::class)->split($expense, [$trip->user_id, $a->id, $b->id]);

        $this->assertSame(['33.34', '33.33', '33.33'], $expense->splits()->orderBy('id')->pluck('share_amount')->all());
    }

    public function test_settle_up_needs_the_fewest_payments(): void
    {
        $payments = app(BudgetService::class)->settlements([
            ['user_id' => 1, 'name' => 'Ana', 'net' => 300.0],
            ['user_id' => 2, 'name' => 'Ben', 'net' => -100.0],
            ['user_id' => 3, 'name' => 'Cy', 'net' => -200.0],
        ]);

        $this->assertSame([
            ['from' => 'Cy', 'from_id' => 3, 'to' => 'Ana', 'to_id' => 1, 'amount' => 200.0],
            ['from' => 'Ben', 'from_id' => 2, 'to' => 'Ana', 'to_id' => 1, 'amount' => 100.0],
        ], $payments);
    }

    public function test_travellers_are_alerted_once_at_80_percent_and_again_at_100_percent(): void
    {
        Notification::fake();
        $trip = Trip::factory()->create(['budget' => 1000]);
        $friend = User::factory()->create();
        $trip->members()->attach($friend, ['role' => 'viewer']);
        $log = fn (int $amount) => $this->actingAs($trip->owner)->post(route('tourist.trips.expenses.store', $trip), [
            'category' => 'food', 'amount' => $amount, 'spent_on' => $trip->start_date->toDateString(),
        ]);

        $log(500);
        Notification::assertNothingSent();

        $log(350)->assertSessionHas('inertia.flash_data.toast.type', 'warning');
        Notification::assertSentTo([$trip->owner, $friend], BudgetThresholdReached::class, fn ($notification) => $notification->threshold === 80);

        $log(50);
        Notification::assertSentTimes(BudgetThresholdReached::class, 2);

        $log(200);
        Notification::assertSentTo($trip->owner, BudgetThresholdReached::class, fn ($notification) => $notification->threshold === 100);
        $this->assertSame(100, $trip->refresh()->budget_alert_level);
    }

    public function test_deleting_expenses_lowers_the_alert_level_so_it_can_fire_again(): void
    {
        Notification::fake();
        $trip = Trip::factory()->create(['budget' => 1000]);
        $expense = Expense::factory()->for($trip)->create(['amount' => 900]);
        app(BudgetService::class)->checkThresholds($trip);
        $this->assertSame(80, $trip->refresh()->budget_alert_level);

        $this->actingAs($trip->owner)->delete(route('tourist.trips.expenses.destroy', [$trip, $expense]));

        $this->assertSame(0, $trip->refresh()->budget_alert_level);
    }

    public function test_the_budget_page_summarises_spending(): void
    {
        $trip = Trip::factory()->create(['budget' => 2000]);
        Expense::factory()->for($trip)->create(['category' => 'food', 'amount' => 300, 'paid_by' => $trip->user_id]);
        Expense::factory()->for($trip)->create(['category' => 'transport', 'amount' => 200, 'paid_by' => $trip->user_id]);

        $this->actingAs($trip->owner)->get(route('tourist.trips.budget', $trip))
            ->assertInertia(fn (Assert $page) => $page
                ->component('tourist/trips/Budget')
                ->where('summary.spent', 500)
                ->where('summary.remaining', 1500)
                ->where('summary.percent', 25)
                ->has('expenses', 2));
    }

    public function test_only_travellers_on_the_trip_can_pay_or_share(): void
    {
        $trip = Trip::factory()->create();
        $stranger = User::factory()->create();

        $this->actingAs($trip->owner)->post(route('tourist.trips.expenses.store', $trip), [
            'category' => 'food', 'amount' => 100, 'spent_on' => '2026-10-10',
            'paid_by' => $stranger->id, 'split_between' => [$stranger->id],
        ])->assertSessionHasErrors(['paid_by', 'split_between.0']);
    }

    public function test_viewers_cannot_log_expenses(): void
    {
        $trip = Trip::factory()->create();
        $viewer = User::factory()->create();
        $trip->members()->attach($viewer, ['role' => 'viewer']);

        $this->actingAs($viewer)->post(route('tourist.trips.expenses.store', $trip), [
            'category' => 'food', 'amount' => 100, 'spent_on' => '2026-10-10',
        ])->assertForbidden();
    }

    public function test_notifications_can_be_read(): void
    {
        $trip = Trip::factory()->create(['budget' => 100]);
        Expense::factory()->for($trip)->create(['amount' => 150]);
        app(BudgetService::class)->checkThresholds($trip);

        $notification = $trip->owner->notifications()->sole();

        $this->actingAs($trip->owner)->get(route('notifications.show', $notification->id))
            ->assertRedirect(route('tourist.trips.budget', $trip, absolute: false));

        $this->assertNotNull($notification->refresh()->read_at);
    }
}
