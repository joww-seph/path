<?php

namespace App\Http\Middleware;

use App\Enums\AdvisorySeverity;
use App\Models\Advisory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'unreadNotifications' => fn () => $request->user()?->unreadNotifications()->count() ?? 0,
            ],
            'locale' => [
                'current' => App::getLocale(),
                'available' => SetLocale::SUPPORTED,
                'translations' => fn () => $this->translations(App::getLocale()),
            ],
            // Named apart from page props such as a listing's own advisories. Cached as plain arrays,
            // since the cache store refuses to unserialize objects.
            'townAdvisories' => fn () => Cache::remember('advisories:town-wide', now()->addMinutes(5), fn () => Advisory::current()
                ->whereDoesntHave('listings')
                ->whereIn('severity', [AdvisorySeverity::Warning, AdvisorySeverity::Danger])
                ->latest('starts_at')
                ->get(['id', 'title', 'body', 'severity', 'ends_at'])
                ->toArray()),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * The interface strings for a language, read from lang/{locale}.json.
     *
     * @return array<string, string>
     */
    private function translations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if ($locale === 'en' || ! is_file($path)) {
            return [];
        }

        return json_decode((string) file_get_contents($path), true) ?: [];
    }
}
