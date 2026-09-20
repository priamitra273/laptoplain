<script setup lang="ts">
import DatePicker from '@/components/DatePicker.vue';
import EmojiPicker from '@/components/EmojiPicker.vue';
import PriorityBadgeSelect from '@/components/PriorityBadgeSelect.vue';
import SeverityBadgeSelect from '@/components/SeverityBadgeSelect.vue';
import type { PrimeSeverity } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { formatDate } from '@/lib/date';
import { parseDate } from '@internationalized/date';
import { useToast } from '@nuxt/ui/composables';
import { computed } from 'vue';

interface StatusOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

interface PriorityOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

defineProps<{
    statuses: StatusOption[];
    priorities: PriorityOption[];
}>();

const emits = defineEmits<{ close: [boolean] }>();

const toast = useToast();

interface ProjectFormData {
    title: string;
    description: string;
    emoji: string;
    start_date: string;
    due_date: string;
    status_id?: string;
    priority_id?: string;
    [key: string]: any;
}

const http = useForm<ProjectFormData>({
    title: '',
    description: '',
    emoji: '🙂',
    start_date: '',
    due_date: '',
    status_id: undefined,
    priority_id: undefined,
});

const startCalendarDate = computed({
    get: () => (http.start_date ? parseDate(http.start_date) : undefined),
    set: (value) => (http.start_date = value ? value.toString() : ''),
});

const dueCalendarDate = computed({
    get: () => (http.due_date ? parseDate(http.due_date) : undefined),
    set: (value) => (http.due_date = value ? value.toString() : ''),
});

const submit = () => {
    http.post(route('project.store'), {
        onSuccess: () => emits('close', true),
        onError: () => toast.add({ title: 'Could not create project', color: 'error', icon: 'i-lucide-circle-alert' }),
    });
};
</script>

<template>
    <USlideover title="Create New Project" :close="{ onClick: () => emits('close', false) }">
        <template #body>
            <div class="flex flex-col gap-4">
                <UFormField name="title" label="Project Title" required :error="http.errors.title || http.errors.emoji">
                    <UInput v-model="http.title" placeholder="Enter Project Title" size="lg" class="w-full">
                        <template #leading>
                            <UPopover>
                                <button type="button" class="flex cursor-pointer items-center justify-center text-base" @click.stop>
                                    {{ http.emoji || '🙂' }}
                                </button>

                                <template #content>
                                    <EmojiPicker :model-value="http.emoji" @update:model-value="(value) => (http.emoji = value)" />
                                </template>
                            </UPopover>
                        </template>
                    </UInput>
                </UFormField>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField name="start_date" label="Start Date" required :error="http.errors.start_date">
                        <DatePicker
                            v-model="startCalendarDate"
                            :label="startCalendarDate ? formatDate(startCalendarDate.toString()) : 'Select date'"
                            trigger-aria-label="Select start date"
                            trigger-class="w-full justify-center"
                        />
                    </UFormField>

                    <UFormField name="due_date" label="Due Date" :error="http.errors.due_date">
                        <DatePicker
                            v-model="dueCalendarDate"
                            :label="dueCalendarDate ? formatDate(dueCalendarDate.toString()) : 'Select date'"
                            trigger-aria-label="Select due date"
                            trigger-class="w-full justify-center"
                        />
                    </UFormField>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UFormField name="status_id" label="Status" required :error="http.errors.status_id">
                        <SeverityBadgeSelect v-model="http.status_id" :items="statuses" placeholder="Select Status" class="w-full" />
                    </UFormField>

                    <UFormField name="priority_id" label="Priority" required :error="http.errors.priority_id">
                        <PriorityBadgeSelect v-model="http.priority_id" :items="priorities" placeholder="Select Priority" class="w-full" />
                    </UFormField>
                </div>

                <UFormField name="description" label="Description" required :error="http.errors.description">
                    <RichTextEditor v-model="http.description" placeholder="What is this project about?" />
                </UFormField>
            </div>
        </template>

        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <UButton label="Cancel" color="neutral" variant="ghost" :disabled="http.processing" @click="emits('close', false)" />
                <UButton label="Save" :loading="http.processing" :disabled="http.processing" @click="submit" />
            </div>
        </template>
    </USlideover>
</template>
