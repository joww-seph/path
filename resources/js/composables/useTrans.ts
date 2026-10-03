import { usePage } from '@inertiajs/vue3';

/**
 * Translate interface text. English strings are the keys; lang/{locale}.json holds the translations.
 *
 * t('Hello, :name', { name: 'Ana' })
 */
export function useTrans() {
    const page = usePage();

    function t(key: string, replace: Record<string, string | number> = {}) {
        const translated = page.props.locale?.translations?.[key] ?? key;

        return Object.entries(replace).reduce(
            (text, [name, value]) => text.replaceAll(`:${name}`, String(value)),
            translated,
        );
    }

    return { t };
}
