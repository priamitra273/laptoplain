<script setup lang="ts">
import { severityColor, severityDotClass } from '@/lib/utils';
import type { PrimeSeverity } from '@/types';
import { DateFormatter, getLocalTimeZone, parseDate } from '@internationalized/date';
import { computed, ref } from 'vue';

interface StatusFilterOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
    count: number;
}

interface PriorityOption {
    id: string;
    name: string;
    severity: PrimeSeverity | null;
}

defineProps<{
    statuses: StatusFilterOption[];
    priorities: PriorityOption[];
    total: number;
}>();

const df = new DateFormatter('en-US', { dateStyle: 'medium' });
const search = defineModel<string>('search', { required: true });
const status = defineModel<string | null>('status', { required: true });
const priorityIds = defineModel<string[]>('priorityIds', { required: true });
const startDate = defineModel<string | null>('startDate', { required: true });
const dueDate = defineModel<string | null>('dueDate', { required: true });

const startCalendarDate = computed({
    get: () => (startDate.value ? parseDate(startDate.value) : undefined),
    set: (value) => (startDate.value = value ? value.toString() : null),
});

const dueCalendarDate = computed({
    get: () => (dueDate.value ? parseDate(dueDate.value) : undefined),
    set: (value) => (dueDate.value = value ? value.toString() : null),
});

const progressRange = defineModel<[number, number]>('progressRange', { required: true });
const expanded = ref(false);

const PRIORITY_ICONS: Record<string, string> = {
    Low: 'i-lucide-signal-low',
    Medium: 'i-lucide-signal-medium',
    High: 'i-lucide-signal-high',
    Critical: 'i-lucide-signal',
};

const priorityIcon = (name: string): string => PRIORITY_ICONS[name] ?? 'i-lucide-signal-low';

</script>

<template>
    <div class="flex flex-col gap-2 py-2.5">
        <div class="flex flex-wrap items-center gap-2">
            <UInput v-model="search" icon="i-lucide-search" placeholder="Search project, or title"
                class="w-full sm:w-72" />

            <UButton label="All" :variant="status === null ? 'solid' : 'outline'" color="neutral" size="sm"
                class="rounded-full" @click="status = null">
                <template #trailing>
                    <span class="text-xs opacity-70">{{ total }}</span>
                </template>
            </UButton>

            <UButton v-for="option in statuses" :key="option.id" :variant="status === option.id ? 'solid' : 'outline'"
                color="neutral" size="sm" class="rounded-full" @click="status = option.id">
                <template #leading>
                    <span class="size-1.5 shrink-0 rounded-full" :class="severityDotClass(option.severity)" />
                </template>

                {{ option.name }}

                <template #trailing>
                    <span class="text-xs opacity-70">{{ option.count }}</span>
                </template>
            </UButton>

            <UButton icon="i-lucide-filter" :label="expanded ? 'Hide' : 'Filters'"
                :variant="expanded ? 'solid' : 'outline'" color="neutral" size="sm" class="ms-auto rounded-full"
                :aria-expanded="expanded" @click="expanded = !expanded" />
        </div>

        <div v-if="expanded" class="flex flex-wrap items-center gap-4 border-t border-default pt-3">
            <USelectMenu v-model="priorityIds" :items="priorities" label-key="name" value-key="id" multiple
                placeholder="Priority" color="neutral" variant="outline" class="w-44 rounded-full">
                <template #default="{ modelValue }">
                    <span v-if="!modelValue || (modelValue as string[]).length === 0">Priority</span>
                    <span v-else class="flex items-center gap-1">
                        {{ (modelValue as string[]).length }} selected

                        <UIcon name="i-lucide-x" class="size-3.5 text-muted hover:text-highlighted"
                            @click.stop.prevent="priorityIds = []" />
                    </span>
                </template>

                <template #item-leading="{ item }">
                    <UIcon :name="priorityIcon(item.name)" class="size-4"
                        :class="`text-${severityColor(item.severity)}`" />
                </template>

                <template #item-label="{ item }">
                    <span :class="`text-${severityColor(item.severity)}`">{{ item.name }}</span>
                </template>
            </USelectMenu>

            <UPopover>
                <UButton
                    :label="startCalendarDate ? df.format(startCalendarDate.toDate(getLocalTimeZone())) : 'Start Date'"
                    icon="i-lucide-calendar" color="neutral" variant="outline" class="w-40 rounded-full" />

                <template #content>
                    <UCalendar v-model="startCalendarDate" class="p-2" />
                </template>
            </UPopover>

            <UPopover>
                <UButton :label="dueCalendarDate ? df.format(dueCalendarDate.toDate(getLocalTimeZone())) : 'Due Date'"
                    icon="i-lucide-calendar" color="neutral" variant="outline" class="w-40 rounded-full" />

                <template #content>
                    <UCalendar v-model="dueCalendarDate" class="p-2" />
                </template>
            </UPopover>

            <UPopover>
                <UButton
                    :label="progressRange[0] === 0 && progressRange[1] === 100 ? 'Progress' : `Progress: ${progressRange[0]}% - ${progressRange[1]}%`"
                    icon="i-lucide-percent" color="neutral" variant="outline" class="rounded-full whitespace-nowrap" />

                <template #content>
                    <div class="w-44 space-y-2 p-3">
                        <USlider v-model="progressRange" :min="0" :max="100" :step="5" />

                        <div class="flex justify-between text-xs text-muted">
                            <span>0%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </template>
            </UPopover>
        </div>
    </div>
</template>
