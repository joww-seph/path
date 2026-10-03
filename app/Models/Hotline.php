<?php

namespace App\Models;

use App\Enums\HotlineType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property HotlineType $type
 * @property string $phone
 * @property string|null $description
 * @property int $position
 */
#[Fillable(['name', 'type', 'phone', 'description', 'position'])]
class Hotline extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => HotlineType::class,
        ];
    }
}
