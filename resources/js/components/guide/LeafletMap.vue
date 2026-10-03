<script setup lang="ts">
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import type * as Leaflet from 'leaflet';
import { onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import type { LatLng } from '@/types';

export type MapMarker = {
    id: number | string;
    lat: number;
    lng: number;
    title: string;
    color?: string;
    label?: string | number;
    popupHtml?: string;
};

const props = withDefaults(
    defineProps<{
        center: LatLng;
        zoom?: number;
        markers?: MapMarker[];
        cluster?: boolean;
        /** Draw a line through the markers in order, e.g. an itinerary route. */
        route?: boolean;
        /** Let the user place a single pin by clicking the map. */
        picker?: boolean;
        pin?: LatLng | null;
        fitMarkers?: boolean;
        userLocation?: LatLng | null;
    }>(),
    {
        zoom: 13,
        markers: () => [],
        cluster: false,
        route: false,
        picker: false,
        pin: null,
        fitMarkers: true,
        userLocation: null,
    },
);

const emit = defineEmits<{
    (e: 'update:pin', value: LatLng): void;
    (e: 'select', id: number | string): void;
}>();

const container = ref<HTMLElement | null>(null);
const map = shallowRef<Leaflet.Map | null>(null);
let L: typeof Leaflet;
let markerLayer: Leaflet.LayerGroup | null = null;
let routeLayer: Leaflet.Polyline | null = null;
let pinMarker: Leaflet.Marker | null = null;
let userMarker: Leaflet.CircleMarker | null = null;
let resizeObserver: ResizeObserver | null = null;

function escapeHtml(text: string) {
    return text.replace(/[&<>"']/g, (char) => `&#${char.charCodeAt(0)};`);
}

function markerIcon(marker: Pick<MapMarker, 'color' | 'label'>) {
    const color = marker.color ?? '#222d60';
    const label =
        marker.label === undefined ? '' : escapeHtml(String(marker.label));

    return L.divIcon({
        className: 'path-marker',
        html: `<span style="background:${color}"><b>${label}</b></span>`,
        iconSize: [28, 28],
        iconAnchor: [14, 28],
        popupAnchor: [0, -26],
    });
}

function drawMarkers() {
    if (!map.value) {
        return;
    }

    markerLayer?.remove();
    routeLayer?.remove();

    markerLayer = props.cluster
        ? (
              L as typeof Leaflet & {
                  markerClusterGroup: (options?: object) => Leaflet.LayerGroup;
              }
          ).markerClusterGroup({
              showCoverageOnHover: false,
              maxClusterRadius: 40,
          })
        : L.layerGroup();

    for (const marker of props.markers) {
        const leafletMarker = L.marker([marker.lat, marker.lng], {
            icon: markerIcon(marker),
            title: marker.title,
            alt: marker.title,
        });

        if (marker.popupHtml) {
            leafletMarker.bindPopup(marker.popupHtml);
        }

        leafletMarker.on('click', () => emit('select', marker.id));
        markerLayer.addLayer(leafletMarker);
    }

    markerLayer.addTo(map.value);

    if (props.route && props.markers.length > 1) {
        routeLayer = L.polyline(
            props.markers.map(
                (marker) => [marker.lat, marker.lng] as [number, number],
            ),
            { color: '#222d60', weight: 4, opacity: 0.7, dashArray: '6 8' },
        ).addTo(map.value);
    }

    fitView();
}

function fitView() {
    if (!map.value || !props.fitMarkers) {
        return;
    }

    if (props.markers.length > 1) {
        map.value.fitBounds(
            L.latLngBounds(
                props.markers.map((marker) => [marker.lat, marker.lng]),
            ),
            { padding: [32, 32], maxZoom: 15 },
        );
    } else if (props.markers.length === 1) {
        map.value.setView([props.markers[0].lat, props.markers[0].lng], 15);
    }
}

function drawPin() {
    if (!map.value || !props.picker) {
        return;
    }

    if (!props.pin) {
        pinMarker?.remove();
        pinMarker = null;

        return;
    }

    if (pinMarker) {
        pinMarker.setLatLng([props.pin.lat, props.pin.lng]);
    } else {
        pinMarker = L.marker([props.pin.lat, props.pin.lng], {
            icon: markerIcon({ color: '#c2410c' }),
            draggable: true,
        }).addTo(map.value);
        pinMarker.on('dragend', () => {
            const position = pinMarker!.getLatLng();
            emit('update:pin', {
                lat: round(position.lat),
                lng: round(position.lng),
            });
        });
    }
}

function drawUserLocation() {
    if (!map.value) {
        return;
    }

    userMarker?.remove();
    userMarker = null;

    if (props.userLocation) {
        userMarker = L.circleMarker(
            [props.userLocation.lat, props.userLocation.lng],
            {
                radius: 8,
                color: '#fff',
                weight: 3,
                fillColor: '#2563eb',
                fillOpacity: 1,
            },
        )
            .bindTooltip('You are here')
            .addTo(map.value);
    }
}

function round(value: number) {
    return Math.round(value * 1e6) / 1e6;
}

onMounted(async () => {
    // Leaflet touches `window`, so load it only in the browser.
    L = (await import('leaflet')).default;

    if (props.cluster) {
        await import('leaflet.markercluster');
    }

    if (!container.value) {
        return;
    }

    map.value = L.map(container.value, { scrollWheelZoom: false }).setView(
        [props.center.lat, props.center.lng],
        props.zoom,
    );

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map.value);

    if (props.picker) {
        map.value.on('click', (event: Leaflet.LeafletMouseEvent) => {
            emit('update:pin', {
                lat: round(event.latlng.lat),
                lng: round(event.latlng.lng),
            });
        });
    }

    drawMarkers();
    drawPin();
    drawUserLocation();

    // The container can change size after mount (grid layout, tabs, dialogs). Leaflet needs to be told,
    // and the view refitted, or it measures a stale size and the markers fall outside the map.
    let lastSize = '';
    resizeObserver = new ResizeObserver(([entry]) => {
        const size = `${Math.round(entry.contentRect.width)}x${Math.round(entry.contentRect.height)}`;

        if (size === lastSize || !map.value) {
            return;
        }

        const firstMeasure =
            lastSize === '' ||
            lastSize.startsWith('0x') ||
            lastSize.endsWith('x0');
        lastSize = size;
        map.value.invalidateSize();

        if (firstMeasure) {
            fitView();
        }
    });
    resizeObserver.observe(container.value);
});

watch(() => props.markers, drawMarkers, { deep: true });
watch(() => props.pin, drawPin, { deep: true });
watch(
    () => props.userLocation,
    () => {
        drawUserLocation();

        if (props.userLocation && map.value) {
            map.value.setView(
                [props.userLocation.lat, props.userLocation.lng],
                14,
            );
        }
    },
);

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    map.value?.remove();
});

defineExpose({
    focus(lat: number, lng: number, zoom = 16) {
        map.value?.setView([lat, lng], zoom);
    },
});
</script>

<template>
    <div ref="container" class="path-map z-0 h-full w-full" />
</template>

<style>
.path-marker span {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: 2px solid white;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    box-shadow: 0 2px 4px rgb(0 0 0 / 0.35);
    color: white;
    font:
        600 12px/1 Figtree,
        sans-serif;
}

.path-marker b {
    transform: rotate(45deg);
    font-weight: 600;
}

.path-map .leaflet-popup-content {
    margin: 10px 12px;
    font-family: Figtree, sans-serif;
}
</style>
