import { onBeforeUnmount, onMounted, readonly, shallowRef } from 'vue';

// Lets a page tailor CMP-BAR-01 while it is shown: PG-04 prefills WhatsApp
// with the project (FR-LEAD-013) and makes Enquire open its dialog. Set
// in onMounted only, so the module state is never written during SSR.

export type ContactBarOverride = {
    /** E.164; null keeps the site number. */
    whatsapp: string | null;
    whatsappText: string | null;
    onEnquire: () => void;
};

const override = shallowRef<ContactBarOverride | null>(null);

/** Read by MobileContactBar. */
export function useContactBarOverride() {
    return readonly(override);
}

/** Call in a page's setup; cleared when the page unmounts. */
export function provideContactBarOverride(
    value: () => ContactBarOverride,
): void {
    let own: ContactBarOverride | null = null;

    onMounted(() => {
        own = value();
        override.value = own;
    });

    onBeforeUnmount(() => {
        if (override.value === own) {
            override.value = null;
        }
    });
}
