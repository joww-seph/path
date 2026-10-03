<?php

namespace App\Models;

use App\Enums\TravelMode;
use App\Enums\TripRole;
use Carbon\CarbonImmutable;
use Database\Factories\TripFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $end_date
 * @property int $pax
 * @property string|null $budget
 * @property int $budget_alert_level
 * @property string $day_starts_at
 * @property TravelMode $travel_mode
 * @property string|null $notes
 * @property string|null $share_token
 * @property-read User $owner
 */
#[Fillable(['title', 'start_date', 'end_date', 'pax', 'budget', 'day_starts_at', 'travel_mode', 'notes'])]
class Trip extends Model
{
    /** @use HasFactory<TripFactory> */
    use HasFactory;

    /**
     * The longest trip PaTH plans, in days.
     */
    public const MAX_DAYS = 14;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'pax' => 1,
        'day_starts_at' => '08:00',
        'travel_mode' => 'car',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'pax' => 'integer',
            'budget' => 'decimal:2',
            'travel_mode' => TravelMode::class,
        ];
    }

    public function dayCount(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function dateForDay(int $dayNumber): CarbonImmutable
    {
        return $this->start_date->addDays($dayNumber - 1);
    }

    /**
     * The visitor's role on this trip, or null if they have no access.
     */
    public function roleFor(?User $user): ?TripRole
    {
        if ($user === null) {
            return null;
        }

        if ($this->user_id === $user->id) {
            return TripRole::Owner;
        }

        $member = $this->relationLoaded('members')
            ? $this->members->firstWhere('id', $user->id)
            : $this->members()->whereKey($user->id)->first();

        return $member?->pivot->role;
    }

    public function enableSharing(): string
    {
        $this->share_token ??= Str::random(32);
        $this->save();

        return $this->share_token;
    }

    /**
     * Trips the user owns or has been invited to.
     *
     * @param  Builder<Trip>  $query
     */
    public function scopeAccessibleBy(Builder $query, User $user): void
    {
        $query->where(fn (Builder $query) => $query
            ->where('user_id', $user->id)
            ->orWhereHas('members', fn (Builder $query) => $query->whereKey($user->id)));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Companions invited to the trip.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'trip_members')->using(TripMember::class)->withPivot('role')->withTimestamps();
    }

    /**
     * The owner and every companion: the people who share the costs.
     *
     * @return Collection<int, User>
     */
    public function travellers()
    {
        return collect([$this->owner])->concat($this->members)->unique('id')->values();
    }

    /**
     * @return HasMany<Expense, $this>
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * @return HasMany<ItineraryItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItineraryItem::class)->orderBy('day_number')->orderBy('position');
    }
}
