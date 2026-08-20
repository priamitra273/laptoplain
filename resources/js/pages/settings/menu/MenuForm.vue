<script setup lang="ts">
import { lucideIconItems } from '@/lib/lucide-icons';
import type { Menu } from '@/types';
import { useForm, type InertiaForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed, watch } from 'vue';

interface AvailableRoute {
    uri: string;
    name: string;
}

interface Props {
    value?: Menu;
    parentMenu?: Menu[];
    availableRoutes?: AvailableRoute[];
}

interface MenuFormData {
    _method: string;
    label: string;
    parent_uuid?: string;
    route_name?: string;
    icon?: string;
    sequence_number: number;
    is_active: boolean;
    [key: string]: any;
}

const props = defineProps<Props>();
const emits = defineEmits<{ close: [boolean] }>();

const title = computed(() => (props.value?.uuid ? 'Edit Menu' : 'Add Menu'));

const toast = useToast();

const form: InertiaForm<MenuFormData> = useForm({
    _method: 'POST',
    label: '',
    parent_uuid: undefined,
    route_name: undefined,
    icon: undefined,
    sequence_number: 1,
    is_active: true,
});

const routes = computed<AvailableRoute[]>(() => {
    const available = props.availableRoutes ?? [];

    if (props.value?.route) {
        return [{ uri: route(props.value.route), name: props.value.route }, ...available];
    }

    return available;
});

watch(
    () => form.parent_uuid,
    (value) => {
        if (!value) {
            form.route_name = undefined;
        }
    },
);

const open = () => {
    form.label = props.value?.label ?? '';
    form.parent_uuid = props.value?.parent_uuid;
    form.route_name = props.value?.route;
    form.icon = props.value?.icon;
    form.sequence_number = props.value?.sequence_number ?? 1;
    form.is_active = props.value?.is_active ?? true;
};

const save = (): void => {
    const url = props.value?.uuid ? route('menu.update', props.value.uuid) : route('menu.store');

    form._method = props.value?.uuid ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            toast.add({ title: 'Success', description: 'Successfully save data', color: 'success' });
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
                    <Label value="Label" required />
                    <UInput v-model="form.label" placeholder="Menu label" />
                    <InputError v-if="form.errors.label" :message="form.errors.label" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Parent" />
                    <USelectMenu
                        v-model="form.parent_uuid"
                        :items="parentMenu"
                        label-key="label"
                        value-key="uuid"
                        placeholder="Select a parent menu"
                        class="w-full"
                    />
                    <InputError v-if="form.errors.parent_uuid" :message="form.errors.parent_uuid" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Route" :required="!!form.parent_uuid" />
                    <USelectMenu
                        v-model="form.route_name"
                        :items="routes"
                        label-key="uri"
                        value-key="name"
                        :disabled="!form.parent_uuid"
                        placeholder="Select a route"
                        class="w-full"
                    />
                    <InputError v-if="form.errors.route_name" :message="form.errors.route_name" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Icon" required />
                    <USelectMenu v-model="form.icon" :items="lucideIconItems" value-key="value" placeholder="Select an icon" virtualize class="w-full">
                        <template #leading="{ modelValue }">
                            <Icon v-if="modelValue" :name="modelValue" class="size-4" />
                        </template>

                        <template #item-leading="{ item }">
                            <Icon :name="item.value" class="size-4" />
                        </template>
                    </USelectMenu>
                    <InputError v-if="form.errors.icon" :message="form.errors.icon" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Sequence" />
                    <UInputNumber v-model="form.sequence_number" :min="1" class="w-full" />
                    <InputError v-if="form.errors.sequence_number" :message="form.errors.sequence_number" />
                </div>

                <div class="flex items-center justify-between gap-2 py-2">
                    <Label value="Active" />
                    <USwitch v-model="form.is_active" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Submit" :loading="form.processing" :disabled="form.processing" @click="save" />
        </template>
    </USlideover>
</template>
