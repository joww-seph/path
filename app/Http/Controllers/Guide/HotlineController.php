<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\Hotline;
use Inertia\Inertia;
use Inertia\Response;

class HotlineController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('guide/Hotlines', [
            'hotlines' => Hotline::orderBy('position')->get()->map(fn (Hotline $hotline) => [
                ...$hotline->only(['id', 'name', 'phone', 'description']),
                'type' => $hotline->type->value,
                'type_label' => $hotline->type->label(),
            ]),
        ]);
    }
}
