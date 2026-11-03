<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { CircleAlert, LoaderCircle } from '@lucide/vue';
import { nextTick, ref, useId, useTemplateRef } from 'vue';
import ConsentField from '@/components/forms/ConsentField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { accepted, email, firstError, required } from '@/lib/forms/rules';
import { notify } from '@/lib/toast';
import { store } from '@/routes/site/newsletter';
import type { SitePageProps } from '@/types/site';

// Newsletter sign-up (spec/leads.md "Newsletter", TOAST-07): email and an
// explicit consent. On success the form is replaced by an inline status
// message (and a toast); the footer and, from 8.1, the article share it.
// Messages follow VAL-02; input is kept on an error (VAL-06).
const props = defineProps<{
    location: 'footer' | 'article';
}>();

const page = usePage<SitePageProps>();
const uid = useId();
const ids = {
    email: `${uid}-email`,
    consent: `${uid}-consent`,
};

const form = useForm({ email: '', consent: false });
const done = ref(false);
const status = useTemplateRef<HTMLElement>('status');

const emailRules = [required('Enter your email address.'), email()];

function validateEmail(): string | null {
    const message = firstError(emailRules, form.email, form.data());

    if (message === null) {
        form.clearErrors('email');
    } else {
        form.setError('email', message);
    }

    return message;
}

function validateConsent(): string | null {
    const message = firstError([accepted()], form.consent, form.data());

    if (message === null) {
        form.clearErrors('consent');
    } else {
        form.setError('consent', message);
    }

    return message;
}

function focusFirstError(): void {
    const target = form.errors.email
        ? ids.email
        : form.errors.consent
          ? ids.consent
          : null;

    if (target) {
        document.getElementById(target)?.focus();
    }
}

async function submit(): Promise<void> {
    const invalid = [validateEmail(), validateConsent()].some(
        (message) => message !== null,
    );

    if (invalid) {
        await nextTick();
        focusFirstError();

        return;
    }

    form.transform((data) => ({
        ...data,
        source_url: window.location.pathname,
        form_location: `newsletter_${props.location}`,
    })).post(store.url(), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: async () => {
            done.value = true;
            notify.success('You are subscribed to project updates.');
            await nextTick();
            status.value?.focus();
        },
        onError: () => {
            void nextTick(focusFirstError);
        },
    });
}
</script>

<template>
    <div
        v-if="done"
        ref="status"
        tabindex="-1"
        role="status"
        class="flex flex-col gap-2 outline-none"
    >
        <p class="font-display text-h4">
            {{ page.props.site.footer.newsletter_success_title }}
        </p>
        <p class="text-muted-foreground">
            {{ page.props.site.footer.newsletter_success_text }}
        </p>
    </div>

    <form
        v-else
        class="flex flex-col gap-3"
        novalidate
        @submit.prevent="submit"
    >
        <div class="flex flex-col gap-2">
            <Label :for="ids.email" required>Email address</Label>
            <div class="flex flex-col gap-3 sm:flex-row">
                <Input
                    :id="ids.email"
                    v-model="form.email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    inputmode="email"
                    maxlength="190"
                    :aria-invalid="form.errors.email ? true : undefined"
                    :aria-describedby="
                        form.errors.email ? `${ids.email}-error` : undefined
                    "
                    class="h-12 sm:flex-1"
                    @blur="validateEmail"
                    @input="form.errors.email && validateEmail()"
                />
                <!-- Same 48 px height as the email field. -->
                <Button
                    type="submit"
                    size="lg"
                    class="h-12"
                    :disabled="form.processing"
                >
                    <LoaderCircle
                        v-if="form.processing"
                        class="animate-spin"
                        aria-hidden="true"
                    />
                    {{ form.processing ? 'Subscribing…' : 'Subscribe' }}
                </Button>
            </div>
            <p
                v-if="form.errors.email"
                :id="`${ids.email}-error`"
                class="flex items-start gap-1.5 text-sm text-destructive"
            >
                <CircleAlert
                    class="mt-0.5 size-4 shrink-0"
                    aria-hidden="true"
                />
                <span>{{ form.errors.email }}</span>
            </p>
        </div>
        <ConsentField
            :id="ids.consent"
            v-model="form.consent"
            :text="page.props.forms.consent.newsletter"
            :privacy-url="page.props.forms.privacy_url"
            :error="form.errors.consent"
            @change="form.errors.consent && validateConsent()"
        />
    </form>
</template>
