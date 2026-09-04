<script setup lang="ts">
import EmojiPicker from '@/components/EmojiPicker.vue';
import { severityColor, severityDotClass } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date';
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

const PRIORITY_ICONS: Record<string, string> = {
    Low: 'i-lucide-signal-low',
    Medium: 'i-lucide-signal-medium',
    High: 'i-lucide-signal-high',
    Critical: 'i-lucide-signal',
};

const priorityIcon = (name: string): string => PRIORITY_ICONS[name] ?? 'i-lucide-signal-low';


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

const df = new DateFormatter('en-US', { dateStyle: 'medium' });

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
                <div class="flex flex-col gap-2">
                    <Label value="Project Title" required />
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
                    <InputError v-if="http.errors.title" :message="http.errors.title" />
                    <InputError v-if="http.errors.emoji" :message="http.errors.emoji" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <Label value="Start Date" required />
                        <UPopover>
                            <UButton
                                :label="startCalendarDate ? df.format(startCalendarDate.toDate(getLocalTimeZone())) : 'Select date'"
                                icon="i-lucide-calendar"
                                color="neutral"
                                variant="outline"
                                block
                            />

                            <template #content>
                                <UCalendar v-model="startCalendarDate" class="p-2" />
                            </template>
                        </UPopover>
                        <InputError v-if="http.errors.start_date" :message="http.errors.start_date" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Due Date" />
                        <UPopover>
                            <UButton
                                :label="dueCalendarDate ? df.format(dueCalendarDate.toDate(getLocalTimeZone())) : 'Select date'"
                                icon="i-lucide-calendar"
                                color="neutral"
                                variant="outline"
                                block
                            />

                            <template #content>
                                <UCalendar v-model="dueCalendarDate" class="p-2" />
                            </template>
                        </UPopover>
                        <InputError v-if="http.errors.due_date" :message="http.errors.due_date" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <Label value="Status" required />
                        <USelectMenu
                            v-model="http.status_id"
                            :items="statuses"
                            label-key="name"
                            value-key="id"
                            placeholder="Select Status"
                            class="w-full"
                        >
                            <template #default="{ modelValue }">
                                <span v-if="!modelValue" class="text-dimmed">Select Status</span>
                                <UBadge v-else color="neutral" variant="subtle" size="sm">
                                    <template #leading>
                                        <span
                                            class="size-1.5 shrink-0 rounded-full"
                                            :class="severityDotClass(statuses.find((s) => s.id === modelValue)?.severity ?? null)"
                                        />
                                    </template>

                                    {{ statuses.find((s) => s.id === modelValue)?.name }}
                                </UBadge>
                            </template>

                            <template #item-label="{ item }">
                                <UBadge color="neutral" variant="subtle" size="sm">
                                    <template #leading>
                                        <span class="size-1.5 shrink-0 rounded-full" :class="severityDotClass(item.severity)" />
                                    </template>

                                    {{ item.name }}
                                </UBadge>
                            </template>
                        </USelectMenu>
                        <InputError v-if="http.errors.status_id" :message="http.errors.status_id" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Priority" required />
                        <USelectMenu
                            v-model="http.priority_id"
                            :items="priorities"
                            label-key="name"
                            value-key="id"
                            placeholder="Select Priority"
                            class="w-full"
                        >
                            <template #default="{ modelValue }">
                                <span v-if="!modelValue" class="text-dimmed">Select Priority</span>
                                <div v-else class="flex items-center gap-1.5" :class="`text-${severityColor(priorities.find((p) => p.id === modelValue)?.severity ?? null)}`">
                                    <UIcon :name="priorityIcon(priorities.find((p) => p.id === modelValue)?.name ?? '')" class="size-4" />
                                    {{ priorities.find((p) => p.id === modelValue)?.name }}
                                </div>
                            </template>

                            <template #item-leading="{ item }">
                                <UIcon :name="priorityIcon(item.name)" class="size-4" :class="`text-${severityColor(item.severity)}`" />
                            </template>

                            <template #item-label="{ item }">
                                <span :class="`text-${severityColor(item.severity)}`">{{ item.name }}</span>
                            </template>
                        </USelectMenu>
                        <InputError v-if="http.errors.priority_id" :message="http.errors.priority_id" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Description" required />
                    <RichTextEditor v-model="http.description" placeholder="What is this project about?" />
                    <InputError v-if="http.errors.description" :message="http.errors.description" />
                </div>
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
