<?php

namespace App\Models;

use App\Enums\AdvisorySeverity;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * An official notice from the tourism office, such as a closure or crowd warning.
 * With no affected listings it applies to the whole town.
 *
 * @property int $id
 * @property int|null $author_id
 * @property string $title
 * @property string $body
 * @property AdvisorySeverity $severity
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property CarbonImmutable|null $notified_at
 */
#[Fillable(['title', 'body', 'severity', 'starts_at', 'ends_at'])]
class Advisory extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'severity' => AdvisorySeverity::class,
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'notified_at' => 'immutable_datetime',
        ];
    }

    /**
     * Advisories in effect at some point between two moments.
     *
     * @param  Builder<Advisory>  $query
     */
    public function scopeActiveBetween(Builder $query, CarbonInterface $from, CarbonInterface $to): void
    {
        $query->where('starts_at', '<=', $to)
            ->where(fn (Builder $query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $from));
    }

    /**
     * @param  Builder<Advisory>  $query
     */
    public function scopeCurrent(Builder $query): void
    {
        $query->activeBetween(now(), now());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsToMany<Listing, $this>
     */
    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class);
    }
}
