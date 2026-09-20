<script setup lang="ts">
import { severityOptions } from '@/constants';
import { lucideIconItems } from '@/lib/lucide-icons';
import { severityColor } from '@/lib/utils';
import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, ref } from 'vue';
import type { TaskCategoryItem } from './Index.vue';

interface Props {
    value?: TaskCategoryItem;
}

interface TaskCategoryFormData {
    _method: string;
    name: string;
    icon: string;
    severity: string;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ close: [boolean] }>();

const title = computed(() => (props.value?.id ? 'Edit Task Category' : 'Add Task Category'));

const form: InertiaForm<TaskCategoryFormData> = useForm({
    _method: 'POST',
    name: '',
    icon: '',
    severity: '',
});

const iconPickerOpen = ref(false);
const iconSearch = ref('');

const filteredIconItems = computed(() => {
    if (!iconSearch.value) return lucideIconItems;

    const query = iconSearch.value.toLowerCase();

    return lucideIconItems.filter((item) => item.label.toLowerCase().includes(query));
});

const selectIcon = (value: string) => {
    form.icon = value;
    iconPickerOpen.value = false;
};

/**
 * Data lama menyimpan ikon dalam format PrimeVue (`pi pi-bolt`) yang tidak bisa dirender
 * lucide-vue-next, jadi nilai seperti itu diperlakukan sebagai belum punya ikon.
 */
const isRenderableIcon = computed(() => !!form.icon && !form.icon.startsWith('pi '));

const open = () => {
    form.name = props.value?.name ?? '';
    form.icon = props.value?.icon ?? '';
    form.severity = props.value?.severity ?? '';
};

const save = (): void => {
    const url = props.value?.id ? route('task-category.update', props.value.id) : route('task-category.store');

    form._method = props.value?.id ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            emits('close', true);
        },
    });
};

// watching form changes
for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            delete form.errors[key];
        },
        {
            debounce: 500,
            maxWait: 1000,
        },
    );
}
</script>

<template>
    <USlideover :title="title" :close="{ onClick: () => emits('close', false) }" @enter="open">
        <template #body>
            <div class="grid gap-6">
                <div class="flex flex-col gap-2">
                    <Label value="Name" required />
                    <UInput v-model="form.name" placeholder="Enter category name" />
                    <InputError v-if="form.errors.name" :message="form.errors.name" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Icon" required />
                    <UPopover v-model:open="iconPickerOpen">
                        <UButton color="neutral" variant="outline" class="w-full justify-start">
                            <Icon v-if="isRenderableIcon" :name="form.icon" class="size-4" />
                            <UIcon v-else name="i-lucide-smile-plus" class="size-4 text-muted" />
                            <span :class="isRenderableIcon ? '' : 'text-muted'">{{ isRenderableIcon ? form.icon : 'Select an icon' }}</span>
                        </UButton>

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
                                        :class="form.icon === item.value ? 'bg-elevated ring-1 ring-primary' : ''"
                                        @click="selectIcon(item.value)"
                                    >
                                        <Icon :name="item.value" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </UPopover>
                    <InputError v-if="form.errors.icon" :message="form.errors.icon" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Severity" required />
                    <USelectMenu v-model="form.severity" :items="severityOptions" value-key="value" placeholder="Select a severity" class="w-full">
                        <template #item-label="{ item }">
                            <UBadge :color="severityColor(item.value)" variant="subtle" size="sm">{{ item.label }}</UBadge>
                        </template>
                    </USelectMenu>
                    <InputError v-if="form.errors.severity" :message="form.errors.severity" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Submit" :loading="form.processing" :disabled="form.processing" @click="save" />
        </template>
    </USlideover>
</template>
