<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Carbon\CarbonImmutable;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Money actually spent on a trip, optionally split between the travellers.
 *
 * @property int $id
 * @property int $trip_id
 * @property int|null $user_id
 * @property int|null $paid_by
 * @property ExpenseCategory $category
 * @property string $amount
 * @property CarbonImmutable $spent_on
 * @property string|null $note
 * @property string|null $client_uuid
 * @property-read Trip $trip
 * @property-read User|null $payer
 */
#[Fillable(['category', 'amount', 'spent_on', 'note', 'paid_by', 'client_uuid'])]
class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
            'spent_on' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<ExpenseSplit, $this>
     */
    public function splits(): HasMany
    {
        return $this->hasMany(ExpenseSplit::class);
    }
}
