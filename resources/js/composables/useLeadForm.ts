import { useForm } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import type { FormDataConvertible, VisitOptions } from '@inertiajs/core';
import { computed, inject, nextTick, provide, ref, useId } from 'vue';
import type { InjectionKey, Ref } from 'vue';
import { readAttribution } from '@/lib/forms/attribution';
import { firstError } from '@/lib/forms/rules';
import type { Rules } from '@/lib/forms/rules';
import type { FormLocation, FormSlug } from '@/types/forms';

/** The error summary's exposed API (components/forms/ErrorSummary.vue). */
type SummaryHandle = { focus: () => void };

export type SummaryItem = { id: string; message: string };

type FormValues = Record<string, FormDataConvertible>;

/** The parts of Inertia's form the composable drives, without its deep generics. */
type LooseForm = {
    errors: Record<string, string | undefined>;
    data: () => Record<string, unknown>;
    clearErrors: (...fields: string[]) => void;
    setError: (
        field: string | Record<string, string>,
        message?: string,
    ) => void;
    transform: (callback: (data: Record<string, unknown>) => object) => void;
    post: (url: string, options?: Omit<VisitOptions, 'data'>) => void;
};

type Config<T extends FormValues> = {
    slug: FormSlug;
    location: FormLocation;
    initial: T;
    rules: Rules<T>;
    /** Extra fields sent with the form (e.g. the project slug). */
    extra?: () => Record<string, FormDataConvertible>;
};

/**
 * The lead-form state shared by the seven forms (CMP-FORM-01, UX-03,
 * VAL-03/06/08): validation on blur, then on change once a field has an
 * error; an error summary that takes focus when a submit fails; server
 * errors mapped to fields; input kept on error. Attribution (UTM, referrer)
 * and the source page are added to the payload at submit time, client-side
 * only (FR-LEAD-001, SEO-REN-08).
 */
export function useLeadForm<T extends FormValues>(config: Config<T>) {
    const uid = useId();
    // Inertia's own generics are too deep for a shared composable: it works
    // on loose values internally and hands the typed form to the component.
    const created = useForm(config.initial as never);
    const loose = created as unknown as LooseForm;
    const form = created as unknown as InertiaForm<T>;
    const summary: Ref<SummaryHandle | null> = ref(null);
    const showSummary = ref(false);
    const order = Object.keys(config.initial);

    loose.transform((data) => ({
        ...data,
        ...config.extra?.(),
        ...readAttribution(),
        source_url: window.location.pathname + window.location.search,
        form_location: config.location,
    }));

    /** DOM id of a field's control, used by labels and summary links. */
    const fieldId = (name: string): string => `${uid}-${name}`;

    /** Message of a field; a key prefix (`documents`) also matches `documents.1`. */
    function error(name: string): string | undefined {
        const errors = loose.errors as Record<string, string | undefined>;

        return (
            errors[name] ??
            Object.entries(errors).find(([key]) =>
                key.startsWith(`${name}.`),
            )?.[1]
        );
    }

    function validateField(name: keyof T & string): string | null {
        const message = firstError(
            config.rules[name],
            loose.data()[name],
            loose.data() as T,
        );

        if (message === null) {
            loose.clearErrors(name);
        } else {
            loose.setError(name, message);
        }

        return message;
    }

    /** Blur: validate the field (UX-03). */
    function onBlur(name: keyof T & string): void {
        validateField(name);
    }

    /** Change: revalidate only once the field has an error (UX-03). */
    function onChange(name: keyof T & string): void {
        if (error(name) !== undefined) {
            validateField(name);
        }
    }

    const items = computed<SummaryItem[]>(() => {
        const errors = loose.errors as Record<string, string | undefined>;
        const keys = Object.keys(errors).sort(
            (a, b) =>
                order.indexOf(a.split('.')[0]) - order.indexOf(b.split('.')[0]),
        );

        return keys.flatMap((key) => {
            const message = errors[key];

            return message ? [{ id: fieldId(key.split('.')[0]), message }] : [];
        });
    });

    /** Unique messages per field, so a files error is listed once. */
    const summaryItems = computed(() => {
        const seen = new Set<string>();

        return items.value.filter((item) => {
            const key = `${item.id}|${item.message}`;

            if (seen.has(key)) {
                return false;
            }

            seen.add(key);

            return true;
        });
    });

    async function focusSummary(): Promise<void> {
        showSummary.value = true;
        await nextTick();
        summary.value?.focus();
    }

    /** Runs every rule; true when the form can be sent. */
    function validateAll(): boolean {
        const errors: Record<string, string> = {};

        for (const name of order) {
            const message = firstError(
                config.rules[name],
                loose.data()[name],
                loose.data() as T,
            );

            if (message !== null) {
                errors[name] = message;
            }
        }

        loose.clearErrors();

        if (Object.keys(errors).length > 0) {
            loose.setError(errors);

            return false;
        }

        return true;
    }

    function submit(options: Omit<VisitOptions, 'data'> = {}): void {
        showSummary.value = false;

        if (!validateAll()) {
            void focusSummary();

            return;
        }

        loose.post(`/forms/${config.slug}`, {
            preserveScroll: true,
            preserveState: true,
            ...options,
            onError: (errors) => {
                void focusSummary();
                options.onError?.(errors);
            },
        });
    }

    const api = {
        slug: config.slug,
        form,
        uid,
        fieldId,
        error,
        onBlur,
        onChange,
        submit,
        summary,
        summaryItems,
        showSummary: computed(
            () => showSummary.value && summaryItems.value.length > 0,
        ),
        focusSummary,
    };

    // The field components (PersonFields, MessageField, …) read the form from here.
    provide(LEAD_FORM, api as unknown as LeadFormApi);

    return api;
}

// The shared field components do not know a form's exact fields.
// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type LeadFormApi = ReturnType<typeof useLeadForm<Record<string, any>>>;

const LEAD_FORM: InjectionKey<LeadFormApi> = Symbol('lead-form');

/** The enclosing form's state, for the shared field components. */
export function useLeadFormContext(): LeadFormApi {
    const lead = inject(LEAD_FORM);

    if (!lead) {
        throw new Error(
            'useLeadFormContext() needs a form that called useLeadForm().',
        );
    }

    return lead;
}
