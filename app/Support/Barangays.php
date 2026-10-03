<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * The 31 barangays of Paoay, read from the homepage map data.
 */
class Barangays
{
    /**
     * @var Collection<int, array{slug: string, name: string}>|null
     */
    private static ?Collection $barangays = null;

    /**
     * @return Collection<int, array{slug: string, name: string}>
     */
    public static function all(): Collection
    {
        return self::$barangays ??= collect(json_decode((string) file_get_contents(resource_path('data/paoay-map.json')), true)['barangays'])
            ->map(fn (array $barangay) => ['slug' => $barangay['slug'], 'name' => $barangay['name']])
            ->sortBy('name')
            ->values();
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return self::all()->pluck('slug')->all();
    }

    public static function name(?string $slug): ?string
    {
        return self::all()->firstWhere('slug', $slug)['name'] ?? null;
    }
}
