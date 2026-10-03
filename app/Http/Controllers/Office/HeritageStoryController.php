<?php

namespace App\Http\Controllers\Office;

use App\Http\Controllers\Controller;
use App\Models\HeritageStory;
use App\Models\Listing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HeritageStoryController extends Controller
{
    public function store(Request $request, Listing $listing): RedirectResponse
    {
        $listing->heritageStories()->create([
            ...$this->validated($request),
            'position' => (int) $listing->heritageStories()->max('position') + 1,
        ]);

        return back();
    }

    public function update(Request $request, Listing $listing, HeritageStory $heritageStory): RedirectResponse
    {
        $heritageStory->update($this->validated($request));

        return back();
    }

    public function destroy(Listing $listing, HeritageStory $heritageStory): RedirectResponse
    {
        $heritageStory->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:10000'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
