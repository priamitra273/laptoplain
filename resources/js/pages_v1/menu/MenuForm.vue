<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Menu } from '@/types';
import { FormDataConvertible } from '@inertiajs/core';
import { useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import * as icons from 'lucide-vue-next';
import { SelectChangeEvent } from 'primevue/select';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';
import { AvailableRoute } from './type';

type FormDataType = Record<string, FormDataConvertible>;

interface Props {
    value?: Menu;
    visible: boolean;
    parent_menu?: Menu[];
    available_routes?: AvailableRoute[];
}

interface DTForm extends FormDataType {
    _method?: string | null;
    label?: string;
    parent_uuid?: string;
    icon?: string;
    route_name?: string;
    sequence_number?: number;
    is_active?: boolean;
    [key: string]: any;
}

const props = defineProps<Props>();

const emits = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>();

const visible = computed({
    get() {
        return props.visible;
    },
    set(newValue) {
        emits('update:visible', newValue);
    },
});

const form = useForm<DTForm>({
    _method: null,
    label: '',
    parent_uuid: '',
    icon: '',
    route_name: '',
    sequence_number: 1,
    is_active: true,
});

const routes = computed<AvailableRoute[]>(() => {
    const availableRoutes = props.available_routes ?? []; // Default to an empty array if undefined

    if (props.value?.uuid) {
        const selectedRoute: AvailableRoute = { uri: route(props.value.route ?? ''), name: props.value.route ?? '' };

        return [selectedRoute, ...availableRoutes];
    }

    return availableRoutes;
});

const title = computed<string>(() => {
    return props.value?.uuid ? 'Edit Menu' : 'Add Menu';
});

const iconList = ref<string[]>([]);

for (const key in icons) {
    if (!key.endsWith('Icon')) {
        iconList.value?.push(key);
    }
}

const onChangeParentMenu = (event: SelectChangeEvent): void => {
    if (!event.value) {
        form.route_name = '';
    }
};

const save = (): void => {
    const url: string = props.value?.uuid ? route('menu.update', props.value?.uuid) : route('menu.store');

    form._method = props.value?.uuid ? 'PUT' : 'POST';

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                html: 'Successfully save data',
            });

            visible.value = false;
        },
    });
};

const onHideDrawer = () => {
    form._method = 'POST';
    form.label = '';
    form.parent_uuid = '';
    form.icon = '';
    form.route_name = '';
    form.sequence_number = 1;
    form.is_active = true;
};

const onShowDrawer = () => {
    if (props.value && props.value.uuid) {
        for (const key in form.data()) {
            form[key as keyof DTForm] = props.value[key];
        }

        form.route_name = props.value.route;
    }
};

for (const key in form.data()) {
    watchDebounced(
        () => form[key],
        () => {
            form.errors[key] = undefined;
        },
        {
            debounce: 500,
            maxWait: 1000,
        },
    );
}
</script>

<template>
    <Drawer v-model:visible="visible" :header="title" class="!w-full md:!w-[40vw]" position="right" @show="onShowDrawer" @after-hide="onHideDrawer">
        <div class="grid gap-2 px-1 py-3 md:grid-cols-2">
            <div>
                <Label>Menu label</Label>
                <InputError :message="form.errors.label" v-if="form.errors.label" />
            </div>

            <InputText v-model="form.label" placeholder="Menu label" :invalid="!!form.errors.label" />
        </div>

        <div class="grid gap-2 px-1 py-3 md:grid-cols-2">
            <Label>Parent menu</Label>
            <Select
                v-model="form.parent_uuid"
                :options="parent_menu"
                option-value="uuid"
                optionLabel="label"
                placeholder="Select a parent menu"
                class="w-full"
                @change="onChangeParentMenu"
                show-clear
            />
        </div>

        <div class="grid gap-2 px-1 py-3 md:grid-cols-2">
            <div>
                <Label>Route Name</Label>
                <InputError :message="form.errors.route_name" v-if="form.errors.route_name" />
            </div>

            <Select
                v-model="form.route_name"
                :options="routes"
                option-value="name"
                optionLabel="uri"
                filter
                show-clear
                placeholder="Select a route"
                class="w-full"
                :disabled="!form.parent_uuid"
            />
        </div>

        <div class="grid gap-2 px-1 py-3 md:grid-cols-2">
            <div>
                <Label>Icon</Label>
                <InputError :message="form.errors.icon" v-if="form.errors.icon" />
            </div>

            <Select
                v-model="form.icon"
                :options="iconList"
                placeholder="Select an Icon"
                class="w-full"
                filter
                :virtualScrollerOptions="{ itemSize: 38 }"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex items-center gap-2">
                        <Icon :name="slotProps.value" class="size-5" />
                        <div>{{ slotProps.value }}</div>
                    </div>
                    <span v-else>
                        {{ slotProps.placeholder }}
                    </span>
                </template>

                <template #option="slotProps">
                    <div class="flex items-center gap-2">
                        <Icon :name="slotProps.option" class="size-5" />
                        <div>{{ slotProps.option }}</div>
                    </div>
                </template>
            </Select>
        </div>

        <div class="grid gap-2 px-1 py-3 md:grid-cols-2">
            <div>
                <Label>Sequence</Label>
                <InputError :message="form.errors.sequence_number" v-if="form.errors.sequence_number" />
            </div>

            <InputNumber v-model="form.sequence_number" class="w-full" :min="1" />
        </div>

        <Label for="is_active" class="grid cursor-pointer grid-cols-2 gap-2 px-1 py-3 hover:bg-surface-100">
            <span>Active</span>
            <ToggleSwitch input-id="is_active" v-model="form.is_active" />
        </Label>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </template>
    </Drawer>
</template>
