<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Booking {{ $booking->code }} · PaTH</title>
    <style>
        @page { margin: 24px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #18214a; }
        .brand { font-size: 9px; letter-spacing: 1px; text-transform: uppercase; color: #9c7454; }
        h1 { font-size: 18px; margin: 4px 0 2px; }
        .code { font-size: 22px; font-weight: bold; letter-spacing: 2px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 4px 0; border-bottom: 1px solid #e6e1d6; vertical-align: top; }
        td.label { color: #5b6280; width: 110px; }
        .qr { text-align: center; margin: 14px 0 6px; }
        .muted { color: #5b6280; }
        .pay { margin-top: 10px; padding: 8px; background: #fbf3e2; border-left: 3px solid #f1bb45; }
    </style>
</head>
<body>
    <p class="brand">PaTH · Paoay Travel Hub · Booking voucher</p>
    <h1>{{ $booking->listing->name }}</h1>
    <p class="muted">{{ $booking->listing->address }}</p>

    <p class="code">{{ $booking->code }}</p>

    @if ($qr)
        <div class="qr"><img src="data:image/svg+xml;base64,{{ base64_encode($qr) }}" width="180" height="180" alt="QR code"></div>
        <p class="muted" style="text-align: center">Show this QR code when you arrive.</p>
    @endif

    <table>
        <tr><td class="label">Date</td><td>{{ $booking->date->format('l, F j, Y') }}@if ($booking->time) · {{ \Carbon\Carbon::parse($booking->time)->format('g:i A') }}@endif</td></tr>
        @if ($booking->nights > 1)
            <tr><td class="label">Nights</td><td>{{ $booking->nights }}</td></tr>
        @endif
        <tr><td class="label">Booked</td><td>{{ $booking->rate_name }}</td></tr>
        <tr><td class="label">Guests</td><td>{{ $booking->pax }}</td></tr>
        <tr><td class="label">Total</td><td><strong>₱{{ number_format((float) $booking->total_amount, 2) }}</strong> (pay the partner directly)</td></tr>
        <tr><td class="label">Status</td><td>{{ $booking->status->label() }}</td></tr>
    </table>

    @if ($booking->listing->business?->payment_instructions)
        <p class="pay"><strong>How to pay:</strong> {{ $booking->listing->business->payment_instructions }}</p>
    @endif
</body>
</html>
