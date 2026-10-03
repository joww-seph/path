import localforage from 'localforage';
import { TILE_URL } from '@/lib/map';
import type { ItineraryDay, PlannerWarning, Trip } from '@/types';

/**
 * Trips saved on the phone for use without signal, plus an outbox of changes made offline.
 * Both live in IndexedDB (through localforage) and are shared by the main app and the /offline page.
 */

export type OfflineTrip = {
    saved_at: string;
    trip: Trip;
    days: ItineraryDay[];
    warnings: PlannerWarning[];
    listings: Record<
        string,
        {
            id: number;
            name: string;
            slug: string;
            summary: string | null;
            address: string | null;
            latitude: number | null;
            longitude: number | null;
            opening_hours: Record<
                string,
                { open: string; close: string }
            > | null;
            entrance_fee: string | null;
            contact_phone: string | null;
        }
    >;
    budget: {
        spent: number;
        budget: number | null;
        remaining: number | null;
        percent: number | null;
    };
    expenses: {
        id: number | string;
        category: string;
        amount: string | number;
        spent_on: string;
        note: string | null;
        pending?: boolean;
    }[];
    travellers: { id: number; name: string }[];
    hotlines: {
        name: string;
        type: string;
        phone: string;
        description: string | null;
    }[];
    emergency_contacts: {
        name: string;
        relationship: string | null;
        phone: string;
    }[];
    vouchers?: unknown[];
    can_update: boolean;
};

export type OutboxOperation = {
    id: string;
    type: 'expense.create' | 'item.update';
    trip_id: number;
    made_at: string;
    data: Record<string, unknown>;
};

const trips = localforage.createInstance({ name: 'path', storeName: 'trips' });
const outbox = localforage.createInstance({
    name: 'path',
    storeName: 'outbox',
});

/**
 * OpenStreetMap's tile policy forbids bulk downloads, so only a small area around the trip's stops
 * is fetched, at street-level zooms. See lib/map.ts to use a provider that allows wider offline use.
 */
const MAX_TILES = 150;
const TILE_ZOOMS = [13, 14, 15];

function csrfHeader(): Record<string, string> {
    const token = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    return token ? { 'X-XSRF-TOKEN': decodeURIComponent(token) } : {};
}

export async function saveTripOffline(tripId: number): Promise<OfflineTrip> {
    const response = await fetch(`/api/trips/${tripId}/offline`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        throw new Error(`Could not download the trip (${response.status}).`);
    }

    const bundle = (await response.json()) as OfflineTrip;
    await trips.setItem(String(tripId), bundle);
    void cacheTiles(bundle);

    return bundle;
}

export async function getOfflineTrip(
    tripId: number,
): Promise<OfflineTrip | null> {
    return trips.getItem<OfflineTrip>(String(tripId));
}

export async function listOfflineTrips(): Promise<OfflineTrip[]> {
    const saved: OfflineTrip[] = [];
    await trips.iterate<OfflineTrip, void>((value) => {
        saved.push(value);
    });

    return saved.sort((a, b) =>
        a.trip.start_date.localeCompare(b.trip.start_date),
    );
}

export async function removeOfflineTrip(tripId: number): Promise<void> {
    await trips.removeItem(String(tripId));
}

export async function updateOfflineTrip(
    tripId: number,
    change: (trip: OfflineTrip) => void,
): Promise<void> {
    const trip = await getOfflineTrip(tripId);

    if (trip) {
        change(trip);
        await trips.setItem(String(tripId), trip);
    }
}

export async function queue(
    operation: Omit<OutboxOperation, 'id' | 'made_at'>,
): Promise<void> {
    const id = crypto.randomUUID();
    await outbox.setItem(`${Date.now()}-${id}`, {
        ...operation,
        id,
        made_at: new Date().toISOString(),
    });
}

export async function outboxCount(): Promise<number> {
    return outbox.length();
}

/**
 * Send queued changes to the server in the order they were made. Operations the server applied,
 * already had, or refused for good are removed; anything else stays for the next try.
 */
export async function flushOutbox(): Promise<{
    sent: number;
    conflicts: number;
}> {
    const keys = (await outbox.keys()).sort();

    if (keys.length === 0 || !navigator.onLine) {
        return { sent: 0, conflicts: 0 };
    }

    const operations = (
        await Promise.all(
            keys.map((key) => outbox.getItem<OutboxOperation>(key)),
        )
    ).filter((operation): operation is OutboxOperation => operation !== null);

    const response = await fetch('/api/sync', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            ...csrfHeader(),
        },
        body: JSON.stringify({ operations }),
    });

    if (!response.ok) {
        return { sent: 0, conflicts: 0 };
    }

    const { results } = (await response.json()) as {
        results: { id: string; status: string }[];
    };
    const done = new Set(results.map((result) => result.id));

    await Promise.all(
        keys.map(async (key) => {
            const operation = await outbox.getItem<OutboxOperation>(key);

            if (operation && done.has(operation.id)) {
                await outbox.removeItem(key);
            }
        }),
    );

    // Refresh saved copies so they show the server's view after syncing.
    const tripIds = [
        ...new Set(operations.map((operation) => operation.trip_id)),
    ];
    await Promise.all(
        tripIds.map((tripId) => saveTripOffline(tripId).catch(() => null)),
    );

    return {
        sent: results.filter((result) => result.status === 'applied').length,
        conflicts: results.filter((result) => result.status === 'conflict')
            .length,
    };
}

function tileFor(lat: number, lng: number, zoom: number) {
    const scale = 2 ** zoom;
    const x = Math.floor(((lng + 180) / 360) * scale);
    const latRad = (lat * Math.PI) / 180;
    const y = Math.floor(
        ((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) /
            2) *
            scale,
    );

    return { x, y };
}

/**
 * Fetch the map tiles around the trip's stops so the service worker keeps them for offline use.
 */
async function cacheTiles(bundle: OfflineTrip): Promise<void> {
    const points = bundle.days
        .flatMap((day) => day.items)
        .filter((item) => item.latitude !== null && item.longitude !== null)
        .map((item) => ({ lat: item.latitude!, lng: item.longitude! }));

    if (points.length === 0 || !navigator.onLine) {
        return;
    }

    const margin = 0.01;
    const south = Math.min(...points.map((point) => point.lat)) - margin;
    const north = Math.max(...points.map((point) => point.lat)) + margin;
    const west = Math.min(...points.map((point) => point.lng)) - margin;
    const east = Math.max(...points.map((point) => point.lng)) + margin;

    const urls: string[] = [];

    for (const zoom of TILE_ZOOMS) {
        const topLeft = tileFor(north, west, zoom);
        const bottomRight = tileFor(south, east, zoom);

        for (let x = topLeft.x; x <= bottomRight.x; x++) {
            for (let y = topLeft.y; y <= bottomRight.y; y++) {
                urls.push(
                    TILE_URL.replace('{z}', String(zoom))
                        .replace('{x}', String(x))
                        .replace('{y}', String(y))
                        .replace('{s}', 'a'),
                );
            }
        }
    }

    for (const url of urls.slice(0, MAX_TILES)) {
        try {
            await fetch(url, { mode: 'no-cors' });
        } catch {
            return;
        }
    }
}
