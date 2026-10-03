import { createApp } from 'vue';
import OfflineApp from '@/offline/OfflineApp.vue';

/**
 * The page shown when there is no signal. It reads the trips saved on the phone; it does not
 * use Inertia because the server may be unreachable.
 */
createApp(OfflineApp).mount('#offline-app');
