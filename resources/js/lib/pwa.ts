import { ref } from 'vue';
import { flushOutbox } from '@/offline/store';

type InstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: string }>;
};

const deferredPrompt = ref<InstallPromptEvent | null>(null);

export const canInstall = ref(false);

/**
 * Register the service worker, remember the browser's install prompt, and send any changes made
 * offline as soon as the phone is back online.
 */
export function initializePwa(): void {
    if (typeof window === 'undefined') {
        return;
    }

    if ('serviceWorker' in navigator && import.meta.env.PROD) {
        const register = () =>
            void navigator.serviceWorker.register('/sw.js', { scope: '/' });

        if (document.readyState === 'complete') {
            register();
        } else {
            window.addEventListener('load', register, { once: true });
        }
    }

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredPrompt.value = event as InstallPromptEvent;
        canInstall.value = true;
    });

    window.addEventListener('appinstalled', () => {
        canInstall.value = false;
    });

    const sync = () => void flushOutbox().catch(() => undefined);
    window.addEventListener('online', sync);
    sync();
}

export async function promptInstall(): Promise<void> {
    if (!deferredPrompt.value) {
        return;
    }

    await deferredPrompt.value.prompt();
    await deferredPrompt.value.userChoice;
    deferredPrompt.value = null;
    canInstall.value = false;
}
