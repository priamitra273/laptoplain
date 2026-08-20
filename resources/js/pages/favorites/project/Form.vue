<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue';
import { lucideIconItems } from '@/lib/lucide-icons';
import { severityColor } from '@/lib/utils';
import { useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
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

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="flex flex-col gap-2">
                        <Label value="Start Date" required />
                        <UInput v-model="form.start_date" type="date" class="w-full" />
                        <InputError v-if="form.errors.start_date" :message="form.errors.start_date" />
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label value="Due Date" />
                        <UInput v-model="form.due_date" type="date" :min="form.start_date || undefined" class="w-full" />
                        <InputError v-if="form.errors.due_date" :message="form.errors.due_date" />
                    </div>
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
