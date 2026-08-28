<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue';
import { lucideIconItems } from '@/lib/lucide-icons';
import { severityColor } from '@/lib/utils';
import { useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import { formatCalendarDate, toCalendarDate } from './date';
import type { ProjectPriorityOption, ProjectStatusOption } from './types';

interface Props {
    statuses?: ProjectStatusOption[];
    priorities?: ProjectPriorityOption[];
}

withDefaults(defineProps<Props>(), {
    statuses: () => [],
    priorities: () => [],
});

const emits = defineEmits<{ close: [boolean] }>();

interface ProjectFormData {
    title: string;
    start_date: string;
    due_date: string;
    status_id?: string;
    priority_id?: string;
    description: string;
    emoji?: string;
    [key: string]: any;
}

const form = useForm<ProjectFormData>({
    title: '',
    start_date: '',
    due_date: '',
    status_id: undefined,
    priority_id: undefined,
    description: '',
    emoji: 'Folder',
});

const iconPickerOpen = ref(false);
const iconSearch = ref('');
const datePickerOpen = ref(false);

/**
 * Start dan due adalah satu rentang, jadi dipilih lewat satu kalender; rentang terbalik
 * jadi mustahil dibuat, tapi rule `after_or_equal` di server tetap ada.
 *
 * Klik pertama pada kalender mengembalikan `{ start, end: undefined }`. Menulisnya
 * langsung ke form akan menghapus due date yang sudah terisi, jadi pilihan setengah jadi
 * ditahan di draf (dalam bentuk string yang sama dengan form) dan baru dipindahkan
 * setelah rentangnya lengkap.
 */
const dateRangeDraft = ref<{ start: string; end: string } | null>(null);

const dateRange = computed({
    get: () => {
        const source = dateRangeDraft.value ?? { start: form.start_date, end: form.due_date };

        return { start: toCalendarDate(source.start) ?? undefined, end: toCalendarDate(source.end) ?? undefined };
    },
    set: (value) => {
        const start = value?.start?.toString() ?? '';
        const end = value?.end?.toString() ?? '';

        if (start && end) {
            form.start_date = start;
            form.due_date = end;
            dateRangeDraft.value = null;
            datePickerOpen.value = false;
            return;
        }

        dateRangeDraft.value = { start, end };
    },
});

// Menutup popover di tengah pemilihan membatalkan draf, bukan menyimpan separuh rentang.
const onDatePickerToggle = (open: boolean) => {
    datePickerOpen.value = open;
    if (!open) {
        dateRangeDraft.value = null;
    }
};

const dateRangeLabel = computed(() => {
    const start = formatCalendarDate(toCalendarDate(form.start_date));
    const end = formatCalendarDate(toCalendarDate(form.due_date));

    if (!start) {
        return 'Select start and due date';
    }

    return end ? `${start} – ${end}` : `${start} – select due date`;
});

// Satu kontrol hanya punya satu slot error, jadi pesan start dipakai lebih dulu.
const dateRangeError = computed(() => form.errors.start_date ?? form.errors.due_date);

const filteredIconItems = computed(() => {
    if (!iconSearch.value) return lucideIconItems;
    const query = iconSearch.value.toLowerCase();
    return lucideIconItems.filter((item) => item.label.toLowerCase().includes(query));
});

const selectIcon = (value: string): void => {
    form.emoji = value;
    iconPickerOpen.value = false;
};

const save = (): void => {
    form.post(route('project.store'), {
        preserveScroll: true,
        onSuccess() {
            emits('close', true);
        },
    });
};

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        { debounce: 500, maxWait: 1000 },
    );
}
</script>

<template>
    <USlideover title="Add Project" :close="{ onClick: () => emits('close', false) }">
        <template #body>
            <div class="grid gap-6">
                <div class="flex flex-col gap-2">
                    <Label value="Title" required />
                    <UInput v-model="form.title" placeholder="Enter Project Title" class="w-full">
                        <template #leading>
                            <UPopover v-model:open="iconPickerOpen">
                                <button type="button" class="flex size-5 items-center justify-center rounded text-muted hover:text-highlighted">
                                    <Icon v-if="form.emoji" :name="form.emoji" class="size-4" />
                                    <UIcon v-else name="i-lucide-smile-plus" class="size-4" />
                                </button>

                                <template #content>
                                    <div class="flex w-64 flex-col gap-2 p-2">
                                        <UInput v-model="iconSearch" icon="i-lucide-search" placeholder="Search icon..." size="sm" autofocus />
                                        <div class="grid max-h-56 grid-cols-6 gap-1 overflow-y-auto">
                                            <button
                                                v-for="item in filteredIconItems"
                                                :key="item.value"
                                                type="button"
                                                :title="item.label"
                                                class="flex size-8 items-center justify-center rounded hover:bg-elevated"
                                                :class="form.emoji === item.value ? 'bg-elevated ring-1 ring-primary' : ''"
                                                @click="selectIcon(item.value)"
                                            >
                                                <Icon :name="item.value" class="size-4" />
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </UPopover>
                        </template>
                    </UInput>
                    <InputError v-if="form.errors.title" :message="form.errors.title" />
                    <InputError v-if="form.errors.emoji" :message="form.errors.emoji" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Start & Due Date" required />
                    <UPopover :open="datePickerOpen" @update:open="onDatePickerToggle">
                        <UButton
                            icon="i-lucide-calendar"
                            :label="dateRangeLabel"
                            color="neutral"
                            variant="outline"
                            block
                            class="justify-start"
                            :class="form.start_date ? '' : 'text-muted'"
                        />

                        <template #content>
                            <UCalendar v-model="dateRange" range :number-of-months="2" class="p-2" />
                        </template>
                    </UPopover>
                    <InputError v-if="dateRangeError" :message="dateRangeError" />
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <Label value="Status" required />
                        <USelectMenu
                            v-model="form.status_id"
                            :items="statuses"
                            label-key="name"
                            value-key="id"
                            placeholder="Select status"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                        <InputError v-if="form.errors.status_id" :message="form.errors.status_id" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Priority" required />
                        <USelectMenu
                            v-model="form.priority_id"
                            :items="priorities"
                            label-key="name"
                            value-key="id"
                            placeholder="Select priority"
                            class="w-full"
                        >
                            <template #item-label="{ item }">
                                <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                            </template>
                        </USelectMenu>
                        <InputError v-if="form.errors.priority_id" :message="form.errors.priority_id" />
                    </div>
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Description" required />
                    <RichTextEditor v-model="form.description" placeholder="Describe this project..." />
                    <InputError v-if="form.errors.description" :message="form.errors.description" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Submit" :loading="form.processing" :disabled="form.processing" @click="save" />
        </template>
    </USlideover>
</template>
