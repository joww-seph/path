<?php

namespace App\Models;

use App\Enums\BusinessType;
use App\Enums\VerificationStatus;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property BusinessType $type
 * @property string|null $permit_no
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $address
 * @property string|null $description
 * @property string|null $payment_instructions
 * @property VerificationStatus $verification_status
 * @property string|null $verification_note
 * @property int|null $verified_by
 * @property Carbon|null $verified_at
 * @property Carbon|null $created_at
 */
#[Fillable(['name', 'type', 'permit_no', 'contact_phone', 'contact_email', 'address', 'description', 'payment_instructions'])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verification_status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => BusinessType::class,
            'verification_status' => VerificationStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function isApproved(): bool
    {
        return $this->verification_status === VerificationStatus::Approved;
    }

    /**
     * @param  Builder<Business>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('verification_status', VerificationStatus::Approved);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Listing, $this>
     */
    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
