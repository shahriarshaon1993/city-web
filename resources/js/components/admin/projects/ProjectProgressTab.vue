<script setup lang="ts">
import { Plus } from '@lucide/vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import MediaIdsField from '@/components/admin/catalog/MediaIdsField.vue';
import RepeaterCard from '@/components/admin/catalog/RepeaterCard.vue';
import MonthField from '@/components/admin/catalog/MonthField.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { newKey } from '@/components/admin/catalog/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { formatMonth } from '@/lib/format';
import type { ProgressForm } from '@/types';
import { countErrors, fid, useCardState, useProjectEditor } from './context';

// Progress (FR-PROJ-005): dated construction updates, newest first, each with a percent, a stage,
// a note and up to 12 photos. A newer update with a lower percent than an older one gets a warning
// (spec validation table), never an error: sometimes the number really goes down.
const { form, options, errors } = useProjectEditor();
const cards = useCardState();

function add(): void {
    const key = newKey('p');
    const latest = form.progress_updates[0];
    const today = new Date();
    const month = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}`;

    form.progress_updates.unshift({
        key,
        reported_on: month,
        percent: latest ? latest.percent : 0,
        stage: latest ? latest.stage : 'foundation',
        note: '',
        photo_ids: [],
    });
    cards.set(key, true);
}

function summary(update: ProgressForm): string {
    const month = /^\d{4}-\d{2}$/.test(update.reported_on)
        ? formatMonth(`${update.reported_on}-01`)
        : '';
    const stage =
        options.progress_stages.find((item) => item.value === update.stage)
            ?.label ?? '';

    return [
        month,
        update.percent !== null && update.percent !== ''
            ? `${update.percent}%`
            : '',
        stage,
    ]
        .filter(Boolean)
        .join(' · ');
}

// The percent of an update is lower than that of an update from an earlier month.
function lowerThanEarlier(update: ProgressForm): boolean {
    const percent = Number(update.percent);

    return form.progress_updates.some(
        (other) =>
            other !== update &&
            other.reported_on !== '' &&
            update.reported_on !== '' &&
            other.reported_on < update.reported_on &&
            Number(other.percent) > percent,
    );
}
</script>

<template>
    <AdminSection
        title="Construction progress"
        description="Dated updates for buyers and NRB clients. Shown newest first on the project page."
    >
        <div id="field-progress_updates" class="grid gap-3">
            <RepeaterCard
                v-for="(update, index) in form.progress_updates"
                :key="update.key"
                :open="
                    cards.isOpen(
                        update.key,
                        countErrors(errors, `progress_updates.${index}`),
                    )
                "
                :title="summary(update) || `Update ${index + 1}`"
                :summary="update.note"
                :errors="countErrors(errors, `progress_updates.${index}`)"
                :noun="`progress update ${index + 1}`"
                @update:open="(open: boolean) => cards.set(update.key, open)"
                @remove="form.progress_updates.splice(index, 1)"
            >
                <div class="grid gap-5">
                    <div class="grid gap-5 md:grid-cols-3">
                        <MonthField
                            :id="fid('progress_updates', index, 'reported_on')"
                            v-model="update.reported_on"
                            label="Month"
                            required
                            :error="
                                errors[`progress_updates.${index}.reported_on`]
                            "
                        />
                        <TextField
                            :id="fid('progress_updates', index, 'percent')"
                            v-model="update.percent"
                            numeric
                            :min="0"
                            :max="100"
                            :step="1"
                            label="Complete (%)"
                            required
                            :error="errors[`progress_updates.${index}.percent`]"
                        />
                        <ChoiceField
                            :id="fid('progress_updates', index, 'stage')"
                            v-model="update.stage"
                            label="Stage"
                            :options="options.progress_stages"
                            :error="errors[`progress_updates.${index}.stage`]"
                        />
                    </div>
                    <p
                        v-if="lowerThanEarlier(update)"
                        class="rounded-md bg-warning-50 px-3 py-2 text-sm text-warning-700"
                        role="note"
                    >
                        This percent is lower than an earlier update. Save it
                        only if the number really went down.
                    </p>

                    <FormField
                        :id="fid('progress_updates', index, 'note')"
                        label="Note"
                        helper="What happened this month. Up to 300 characters."
                        :counter="{ length: update.note.length, max: 300 }"
                        :error="errors[`progress_updates.${index}.note`]"
                    >
                        <template #default="{ describedby, invalid }">
                            <Textarea
                                :id="fid('progress_updates', index, 'note')"
                                v-model="update.note"
                                rows="3"
                                maxlength="300"
                                :aria-invalid="invalid || undefined"
                                :aria-describedby="describedby"
                            />
                        </template>
                    </FormField>

                    <FormField
                        :id="fid('progress_updates', index, 'photo_ids')"
                        label="Photos"
                        helper="Up to 12 site photos from the library."
                        :error="errors[`progress_updates.${index}.photo_ids`]"
                    >
                        <template #default="{ describedby, invalid }">
                            <MediaIdsField
                                :id="
                                    fid('progress_updates', index, 'photo_ids')
                                "
                                v-model="update.photo_ids"
                                :max="options.limits.photos"
                                picker-title="Choose progress photos"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </div>
            </RepeaterCard>

            <p
                v-if="!form.progress_updates.length"
                class="text-sm text-muted-foreground"
            >
                No progress updates yet. They appear on the project page once
                added.
            </p>
            <div>
                <Button type="button" variant="outline" size="sm" @click="add">
                    <Plus aria-hidden="true" />
                    Add update
                </Button>
            </div>
        </div>
    </AdminSection>
</template>
