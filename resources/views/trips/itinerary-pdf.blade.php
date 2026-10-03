<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $trip['title'] }} · PaTH itinerary</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #18214a; }
        h1 { font-size: 22px; margin: 0; }
        h2 { font-size: 14px; margin: 18px 0 6px; padding-bottom: 4px; border-bottom: 2px solid #f1bb45; }
        .muted { color: #5b6280; }
        .brand { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: #9c7454; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 4px; vertical-align: top; border-bottom: 1px solid #e6e1d6; }
        td.time { width: 80px; white-space: nowrap; font-weight: bold; }
        td.travel { color: #5b6280; font-size: 10px; padding: 2px 4px 2px 84px; border-bottom: none; }
        .notes { color: #5b6280; font-style: italic; }
        .warning { color: #9a3412; }
        .done { text-decoration: line-through; color: #8a8fa8; }
        .hotlines td { border-bottom: none; padding: 2px 4px; }
        .footer { margin-top: 24px; font-size: 9px; color: #8a8fa8; }
    </style>
</head>
<body>
    <p class="brand">PaTH · Paoay Travel Hub</p>
    <h1>{{ $trip['title'] }}</h1>
    <p class="muted">
        {{ \Carbon\Carbon::parse($trip['start_date'])->format('F j, Y') }}
        @if ($trip['day_count'] > 1)
            – {{ \Carbon\Carbon::parse($trip['end_date'])->format('F j, Y') }}
        @endif
        · {{ $trip['day_count'] }} {{ Str::plural('day', $trip['day_count']) }}
        · {{ $trip['pax'] }} {{ Str::plural('traveller', $trip['pax']) }}
        @if ($estimatedCost > 0)
            · Estimated fees ₱{{ number_format($estimatedCost) }}
        @endif
    </p>

    @foreach ($days as $day)
        <h2>
            Day {{ $day['number'] }} · {{ \Carbon\Carbon::parse($day['date'])->format('l, F j') }}
        </h2>

        @if (count($day['items']) === 0)
            <p class="muted">Nothing planned yet.</p>
        @else
            <table>
                @foreach ($day['items'] as $item)
                    @if (! $loop->first && $item['travel_minutes_from_previous'])
                        <tr><td colspan="2" class="travel">↓ About {{ $item['travel_minutes_from_previous'] }} min travel ({{ number_format($item['distance_km_from_previous'], 1) }} km)</td></tr>
                    @endif
                    <tr>
                        <td class="time">{{ $item['start_time'] ? \Carbon\Carbon::parse($item['start_time'])->format('g:i A') : '' }}</td>
                        <td>
                            <strong class="{{ $item['is_done'] ? 'done' : '' }}">{{ $item['title'] }}</strong>
                            <span class="muted">· {{ $item['visit_minutes'] }} min</span>
                            @if ($item['listing'] && $item['listing']['address'])
                                <br><span class="muted">{{ $item['listing']['address'] }}</span>
                            @endif
                            @if ($item['listing'] && $item['listing']['contact_phone'])
                                <br><span class="muted">Tel. {{ $item['listing']['contact_phone'] }}</span>
                            @endif
                            @if ($item['notes'])
                                <br><span class="notes">{{ $item['notes'] }}</span>
                            @endif
                            @foreach (collect($warnings)->where('item_id', $item['id']) as $warning)
                                <br><span class="warning">⚠ {{ $warning['message'] }}</span>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    @endforeach

    @if ($hotlines->isNotEmpty())
        <h2>Emergency hotlines</h2>
        <table class="hotlines">
            @foreach ($hotlines as $hotline)
                <tr><td style="width: 200px">{{ $hotline->name }}</td><td><strong>{{ $hotline->phone }}</strong></td></tr>
            @endforeach
        </table>
    @endif

    <p class="footer">
        Times are estimates based on typical visit lengths and travel by {{ $trip['travel_mode'] }}. Check opening hours and advisories in PaTH before you go.
    </p>
</body>
</html>
