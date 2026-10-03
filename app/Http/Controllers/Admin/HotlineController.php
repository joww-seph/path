<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HotlineType;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Hotline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HotlineController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/Hotlines', [
            'hotlines' => Hotline::orderBy('position')->get()->map(fn (Hotline $hotline) => [
                ...$hotline->only(['id', 'name', 'phone', 'description', 'position']),
                'type' => $hotline->type->value,
            ]),
            'types' => HotlineType::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $hotline = Hotline::create([...$this->validated($request), 'position' => (int) Hotline::max('position') + 1]);

        ActivityLog::record('hotline.created', $hotline);

        return back();
    }

    public function update(Request $request, Hotline $hotline): RedirectResponse
    {
        $hotline->update($this->validated($request));

        ActivityLog::record('hotline.updated', $hotline);

        return back();
    }

    public function destroy(Hotline $hotline): RedirectResponse
    {
        ActivityLog::record('hotline.deleted', $hotline, ['name' => $hotline->name, 'phone' => $hotline->phone]);

        $hotline->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::enum(HotlineType::class)],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{3,30}$/'],
            'description' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
    }
}
