import { usePage } from '@inertiajs/svelte';

function resolveTranslation(messages, key) {
    return key.split('.').reduce((value, part) => value?.[part], messages);
}

function interpolate(message, replacements) {
    return Object.entries(replacements).reduce((translatedMessage, [key, value]) => {
        return translatedMessage.replaceAll(`:${key}`, String(value));
    }, message);
}

export function useStorefrontTranslations() {
    const page = usePage();

    function t(key, fallback = key, replacements = {}) {
        const translation = resolveTranslation(page.props.translations ?? {}, key);
        const message = typeof translation === 'string' ? translation : fallback;

        return interpolate(message, replacements);
    }

    return { t };
}
