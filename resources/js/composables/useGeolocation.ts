import { ref } from 'vue';
import type { LatLng } from '@/types';

/**
 * Ask the browser for the visitor's position, only when they tap a button.
 */
export function useGeolocation() {
    const position = ref<LatLng | null>(null);
    const locating = ref(false);
    const error = ref<string | null>(null);

    function locate(): Promise<LatLng | null> {
        if (!('geolocation' in navigator)) {
            error.value = 'Your browser cannot share your location.';

            return Promise.resolve(null);
        }

        locating.value = true;
        error.value = null;

        return new Promise((resolve) => {
            navigator.geolocation.getCurrentPosition(
                (result) => {
                    position.value = {
                        lat: Math.round(result.coords.latitude * 1e6) / 1e6,
                        lng: Math.round(result.coords.longitude * 1e6) / 1e6,
                    };
                    locating.value = false;
                    resolve(position.value);
                },
                () => {
                    error.value =
                        'We could not get your location. Check that location access is allowed.';
                    locating.value = false;
                    resolve(null);
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 },
            );
        });
    }

    return { position, locating, error, locate };
}
