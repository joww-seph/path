<?php

namespace App\Models;

use App\Enums\ListingStatus;
use App\Support\Geo;
use Carbon\CarbonInterface;
use Database\Factories\ListingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An attraction, business or service shown in PaTH.
 *
 * Opening hours are stored per weekday, e.g. {"mon": {"open": "08:00", "close": "17:00"}}.
 * A missing day means closed; null hours mean the hours are not known.
 *
 * @property int $id
 * @property int|null $business_id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $summary
 * @property string|null $description
 * @property string|null $barangay
 * @property string|null $address
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array<string, array{open: string, close: string}>|null $opening_hours
 * @property string|null $entrance_fee
 * @property string|null $price_min
 * @property string|null $price_max
 * @property int $visit_minutes
 * @property string|null $contact_phone
 * @property string|null $contact_email
 * @property string|null $website
 * @property string|null $facebook_url
 * @property bool $is_bookable
 * @property int|null $default_daily_slots
 * @property bool $is_accessible
 * @property bool $is_featured
 * @property ListingStatus $status
 * @property string|null $review_note
 * @property string $rating_average
 * @property int $reviews_count
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business|null $business
 * @property-read Category $category
 */
#[Fillable([
    'category_id', 'name', 'summary', 'description', 'barangay', 'address', 'latitude', 'longitude',
    'opening_hours', 'entrance_fee', 'price_min', 'price_max', 'visit_minutes', 'contact_phone',
    'contact_email', 'website', 'facebook_url', 'is_bookable', 'is_accessible', 'default_daily_slots',
])]
class Listing extends Model
{
    /** @use HasFactory<ListingFactory> */
    use HasFactory;

    public const WEEKDAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'visit_minutes' => 60,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'opening_hours' => 'array',
            'entrance_fee' => 'decimal:2',
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'is_bookable' => 'boolean',
            'is_accessible' => 'boolean',
            'is_featured' => 'boolean',
            'status' => ListingStatus::class,
            'rating_average' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Listing $listing) {
            $listing->slug ??= static::uniqueSlug($listing->name);
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'listing';
        $slug = $base;
        $suffix = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === ListingStatus::Published;
    }

    /**
     * Whether the listing is open at some point on the given day. Unknown hours count as open.
     */
    public function isOpenOn(CarbonInterface $date): bool
    {
        if ($this->opening_hours === null) {
            return true;
        }

        return isset($this->opening_hours[self::weekdayKey($date)]);
    }

    /**
     * @return array{open: string, close: string}|null
     */
    public function hoursOn(CarbonInterface $date): ?array
    {
        return $this->opening_hours[self::weekdayKey($date)] ?? null;
    }

    public static function weekdayKey(CarbonInterface $date): string
    {
        return self::WEEKDAYS[$date->dayOfWeekIso - 1];
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function distanceKmFrom(float $lat, float $lng): ?float
    {
        return $this->hasLocation()
            ? Geo::distanceKm($lat, $lng, $this->latitude, $this->longitude)
            : null;
    }

    /**
     * The lowest price a visitor pays: an entrance fee, the cheapest rate, or the starting price.
     */
    protected function startingPrice(): Attribute
    {
        return Attribute::get(function (): ?float {
            $candidates = array_filter([
                $this->entrance_fee,
                $this->price_min,
                $this->relationLoaded('rates') ? $this->rates->min('price') : null,
            ], fn ($value) => $value !== null);

            return $candidates === [] ? null : (float) min($candidates);
        });
    }

    /**
     * @param  Builder<Listing>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ListingStatus::Published);
    }

    /**
     * @param  Builder<Listing>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $query) => $query->where(
            fn (Builder $query) => $query
                ->where('name', 'like', "%{$term}%")
                ->orWhere('summary', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('barangay', 'like', "%{$term}%"),
        ));
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ListingRate, $this>
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ListingRate::class)->orderBy('position')->orderBy('price');
    }

    /**
     * @return HasMany<ListingPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasOne<ListingPhoto, $this>
     */
    public function coverPhoto(): HasOne
    {
        return $this->hasOne(ListingPhoto::class)->ofMany(['position' => 'min', 'id' => 'min']);
    }

    /**
     * @return HasMany<HeritageStory, $this>
     */
    public function heritageStories(): HasMany
    {
        return $this->hasMany(HeritageStory::class)->orderBy('position');
    }

    /**
     * @return HasMany<AvailabilityBlock, $this>
     */
    public function availability(): HasMany
    {
        return $this->hasMany(AvailabilityBlock::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'venue_listing_id');
    }
}
