<?php

namespace App\Models;

use Database\Factories\TouristProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property list<string>|null $interests
 * @property int $group_size
 * @property int|null $budget_min
 * @property int|null $budget_max
 * @property list<string>|null $accessibility_needs
 * @property string|null $home_province
 * @property string $home_country
 */
#[Fillable(['interests', 'group_size', 'budget_min', 'budget_max', 'accessibility_needs', 'home_province', 'home_country'])]
class TouristProfile extends Model
{
    /** @use HasFactory<TouristProfileFactory> */
    use HasFactory;

    public const INTERESTS = ['heritage', 'nature', 'adventure', 'food', 'beach', 'shopping', 'photography', 'faith'];

    public const ACCESSIBILITY_NEEDS = ['wheelchair', 'limited_walking', 'visual', 'hearing', 'senior', 'small_children'];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'group_size' => 1,
        'home_country' => 'PH',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'interests' => 'array',
            'accessibility_needs' => 'array',
            'group_size' => 'integer',
            'budget_min' => 'integer',
            'budget_max' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
