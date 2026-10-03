<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Explore the 31 barangays of Paoay, Ilocos Norte.">

        <title>Paoay Travel Hub</title>

        @fonts
        @vite(['resources/css/home.css', 'resources/js/home.js'])
    </head>
    <body class="weave">
        <div class="home">
            <header class="intro">
                <p class="brand"><span class="brand-mark">PaTH</span> Paoay Travel Hub</p>

                <nav class="topnav" aria-label="Main">
                    <a href="{{ route('explore') }}">Explore</a>
                    <a href="{{ route('map') }}">Map</a>
                    <a href="{{ route('events.index') }}">Events</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="topnav-cta">My dashboard</a>
                    @else
                        <a href="{{ route('login') }}">Log in</a>
                        <a href="{{ route('register') }}" class="topnav-cta">Plan a trip</a>
                    @endauth
                </nav>

                <h1 class="town">Paoay</h1>
                <p class="lede">
                    Thirty-one barangays fit together to make the town, from the dunes on the coast to the hills past the lake.
                    Choose one to visit its page.
                </p>

                <p class="readout" aria-live="polite">
                    <span class="readout-hint" data-readout-hint>Choose a barangay on the map</span>
                    <span class="readout-name" data-readout-name hidden></span>
                </p>

                <nav class="directory" aria-label="Barangays of Paoay">
                    <ul>
                        @foreach ($barangays->sortBy('name') as $barangay)
                            <li>
                                <a href="{{ $barangay['url'] }}" target="_blank" rel="noopener" data-barangay="{{ $barangay['slug'] }}">
                                    {{ $barangay['name'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </header>

            <main class="stage">
                <svg class="map" viewBox="{{ $viewBox }}" role="img" aria-labelledby="map-title">
                    <title id="map-title">Map of Paoay divided into its barangays</title>

                    <path class="land" d="{{ $silhouette }}" fill-rule="evenodd" />
                    <path class="lake" d="{{ $lake }}" />

                    <g class="pieces">
                        @foreach ($barangays as $barangay)
                            <a
                                href="{{ $barangay['url'] }}"
                                target="_blank"
                                rel="noopener"
                                class="piece tone-{{ $loop->index % 5 }}"
                                id="piece-{{ $barangay['slug'] }}"
                                data-barangay="{{ $barangay['slug'] }}"
                                data-name="{{ $barangay['name'] }}"
                                aria-label="{{ $barangay['name'] }}"
                                style="--order: {{ $barangay['order'] }}; --dx: {{ $barangay['scatter']['x'] }}px; --dy: {{ $barangay['scatter']['y'] }}px; --rot: {{ $barangay['scatter']['r'] }}deg"
                            >
                                <path id="shape-{{ $barangay['slug'] }}" d="{{ $barangay['d'] }}" />
                            </a>
                        @endforeach
                    </g>

                    <g class="labels" aria-hidden="true">
                        <text class="lake-label" x="{{ $lakeLabel[0] }}" y="{{ $lakeLabel[1] }}">Paoay Lake</text>
                        @foreach ($barangays->filter(fn ($barangay) => $barangay['area'] > 1800 && strlen($barangay['name']) <= 12) as $barangay)
                            <text x="{{ $barangay['label'][0] }}" y="{{ $barangay['label'][1] }}">{{ $barangay['name'] }}</text>
                        @endforeach
                    </g>

                    {{-- The piece being pointed at is redrawn here so it sits above its neighbours. --}}
                    <use class="lifted" data-lifted href="" aria-hidden="true" />
                </svg>
            </main>
        </div>
    </body>
</html>
