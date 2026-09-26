<script setup lang="ts">
import ProgressWithLabel from '@/components/common/ProgressWithLabel.vue';
import { FetchJsonError, fetchJson, isTaskStatusDone, plural } from '@/lib/utils';
import { computed, ref } from 'vue';
import type { BacklogSprint } from './types';

const props = defineProps<{
    projectId: string;
    sprint: BacklogSprint;
    otherSprints: Pick<BacklogSprint, 'id' | 'name'>[];
}>();

const emit = defineEmits<{ close: [boolean] }>();

const incompleteTasks = computed(() => props.sprint.tasks.filter((task) => !isTaskStatusDone(task.status?.name)));
const doneCount = computed(() => props.sprint.tasks.length - incompleteTasks.value.length);
const donePercentage = computed(() => (props.sprint.tasks.length ? Math.round((doneCount.value / props.sprint.tasks.length) * 100) : 0));

/**
 * Sprint tujuan dan dua tujuan khusus (backlog / sprint baru) digabung jadi satu daftar
 * supaya pemilihannya satu langkah. Sprint diberi prefix agar tetap bisa dibedakan dari
 * tujuan khusus saat payload disusun.
 */
const SPRINT_PREFIX = 'sprint:';

const targets = computed(() => [
    { value: 'backlog', label: 'Backlog', icon: 'i-lucide-inbox' },
    ...props.otherSprints.map((sprint) => ({ value: `${SPRINT_PREFIX}${sprint.id}`, label: sprint.name, icon: 'i-lucide-calendar-range' })),
    { value: 'new_sprint', label: 'New sprint', icon: 'i-lucide-plus' },
]);

const target = ref('backlog');
const retrospective = ref('');

const failureMessage = ref('');
const processing = ref(false);

const moveIncompleteTo = computed(() => {
    if (!incompleteTasks.value.length) {
        return {};
    }

    return target.value.startsWith(SPRINT_PREFIX) ? { existing_sprint: target.value.slice(SPRINT_PREFIX.length) } : { other: target.value };
});

const submit = async () => {
    processing.value = true;
    failureMessage.value = '';

    try {
        await fetchJson(route('project.sprints.complete', { projectEncoded: props.projectId, sprintEncoded: props.sprint.id }), 'PATCH', {
            retrospective: retrospective.value || null,
            move_incomplete_to: moveIncompleteTo.value,
        });

        emit('close', true);
    } catch (error) {
        failureMessage.value = error instanceof FetchJsonError ? error.message : 'Could not complete the sprint.';
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <UModal
        :title="`Complete ${sprint.name}`"
        :ui="{ content: 'sm:max-w-md', header: 'px-6 py-5', body: 'p-6', footer: 'justify-end gap-2 px-6 py-4' }"
    >
        <template #body>
            <div class="grid gap-6">
                <UAlert v-if="failureMessage" color="error" variant="soft" :description="failureMessage" />

                <!-- Sprint tanpa task tidak menampilkan ringkasan: "0 dari 0" dan bar 0% tidak bermakna. -->
                <template v-if="sprint.tasks.length">
                    <div class="rounded-lg border border-default bg-elevated/40 px-4 py-3">
                        <p class="text-sm text-muted">
                            <strong class="text-base font-semibold text-highlighted tabular-nums">{{ doneCount }}</strong>
                            of {{ plural(sprint.tasks.length, 'task') }} done
                        </p>

                        <ProgressWithLabel :value="donePercentage" :bar-aria-label="`Progress ${sprint.name}`" class="mt-2.5" />
                    </div>

                    <UFormField v-if="incompleteTasks.length" label="Move unfinished tasks to">
                        <template #hint>
                            <UBadge color="warning" variant="subtle" size="sm">{{ plural(incompleteTasks.length, 'task') }}</UBadge>
                        </template>

                        <USelectMenu v-model="target" :items="targets" value-key="value" :search-input="false" class="w-full" />
                    </UFormField>

                    <p v-else class="flex items-center gap-2 text-sm text-success">
                        <UIcon name="i-lucide-circle-check" class="size-4 shrink-0" />
                        All tasks in this sprint are done.
                    </p>
                </template>

                <p v-else class="flex items-center gap-2 text-sm text-muted">
                    <UIcon name="i-lucide-inbox" class="size-4 shrink-0" />
                    This sprint has no tasks yet.
                </p>

                <UFormField>
                    <template #label> Retrospective <span class="font-normal text-dimmed">(optional)</span> </template>

                    <UTextarea v-model="retrospective" :rows="3" placeholder="Short notes on how the sprint went..." class="w-full" />
                </UFormField>
            </div>
        </template>

        <template #footer>
            <UButton
                color="neutral"
                variant="outline"
                label="Cancel"
                class="flex-1 justify-center sm:ml-auto sm:flex-none"
                :disabled="processing"
                @click="emit('close', false)"
            />
            <UButton
                label="Complete Sprint"
                class="flex-1 justify-center sm:flex-none"
                :loading="processing"
                :disabled="processing"
                @click="submit"
            />
        </template>
    </UModal>
</template>
