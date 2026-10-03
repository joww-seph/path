/**
 * Map tiles. OpenStreetMap by default; set VITE_MAP_TILE_URL (and its attribution) to use a
 * provider that allows offline caching of a wider area.
 */
export const TILE_URL =
    import.meta.env.VITE_MAP_TILE_URL ||
    'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

export const TILE_ATTRIBUTION =
    import.meta.env.VITE_MAP_TILE_ATTRIBUTION ||
    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
