<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ready-made plan, such as "Paoay in One Day", that tourists can start a trip from.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $summary
 * @property int $days
 * @property bool $is_published
 */
#[Fillable(['name', 'slug', 'summary', 'days', 'is_published'])]
class ItineraryTemplate extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TemplateItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(TemplateItem::class)->orderBy('day_number')->orderBy('position');
    }
}
