<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2'
import Label from '@/components/ui/label/Label.vue';
import { watchDebounced } from '@vueuse/core';
import { Project } from '@/types';
import moment from 'moment';

interface Props {
    value?: Project;
    visible: boolean;
}

interface ProjectForm {
    _method: "POST" | "PUT";
    name: string;
    start_date: Date | null;
    finish_date: Date | null;
    plan_site: number | null;
    plan_cctv: number | null;
    [key: string]: any;
}

const props = defineProps<Props>()

const emits = defineEmits<{
    (event: 'update:visible', value: boolean): void;
}>()

const visible = computed<boolean>({
    get() {
        return props.visible
    },
    set(newValue) {
        emits('update:visible', newValue)
    }
});

const formHeader = computed(() => {
    return props.value?.uuid ? 'Edit Project' : 'Create New Project'
})

const form: InertiaForm<ProjectForm> = useForm({
    _method: 'POST',
    name: '',
    start_date: null,
    finish_date: null,
    plan_cctv: null,
    plan_site: null
});

const save = (): void => {
    const url = props.value?.uuid ? route('project.update', props.value.uuid) : route('project.store');

    form._method = props.value?.uuid ? 'PUT' : 'POST'

    form
        .transform((data) => ({
            ...data,
            start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
            finish_date: data.finish_date ? moment(data.finish_date).format('YYYY-MM-DD') : null
        }))
        .post(url, {
            preserveScroll: true,
            onSuccess() {
                Swal.fire('Success', 'Successfully save data', 'success')
                visible.value = false
            }
        })
}

const hide = (): void => {
    form._method = 'POST'

}

const show = (): void => {
    form.name = props.value?.name ?? '';
    form.start_date = props.value?.start_date ? moment(props.value?.start_date).toDate() : null;
    form.finish_date = props.value?.finish_date ? moment(props.value?.finish_date).toDate() : null;
    form.plan_site = props.value?.plan_site ?? null;
    form.plan_cctv = props.value?.plan_cctv ?? null;
}

// watching form changes
for (const key in form.data()) {
    watchDebounced(() => form[key], () => {
        delete form.errors[key]
    }, { debounce: 500, maxWait: 1000 })
}

</script>

<template>
    <Drawer v-model:visible="visible" class="!w-full md:!w-[40vw]" position="right" :header="formHeader" @show="show"
        @after-hide="hide">
        <form class="grid md:grid-cols-2 gap-8" @submit.prevent="save">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="name">Project Name</Label>
                <InputText v-model="form.name" id="name" placeholder="Enter Project Name" />
                <InputError :message="form.errors.name" v-if="form.errors.name" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="start_date">Start Date</Label>
                <DatePicker v-model="form.start_date" input-id="start_date" show-icon fluid date-format="yy-mm-dd"
                    placeholder="Eneter Start Date" />
                <InputError :message="form.errors.start_date" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="finish_date">Finish Date</Label>
                <DatePicker v-model="form.finish_date" input-id="finish_date" show-icon fluid date-format="yy-mm-dd"
                    :min-date="form.start_date ?? undefined" placeholder="Eneter Finish Date" />
                <InputError :message="form.errors.finish_date" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="plan_site">Plan Site</Label>
                <InputNumber v-model="form.plan_site" input-id="plan_site" fluid placeholder="Enter Plan Site" />
                <InputError :message="form.errors.plan_site" />
            </div>

            <div class="flex flex-col gap-2">
                <Label for="plan_cctv">Plan CCTV</Label>
                <InputNumber v-model="form.plan_cctv" input-id="plan_cctv" fluid placeholder="Enter Plan CCTV" />
                <InputError :message="form.errors.plan_cctv" />
            </div>
        </form>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" :loading="form.processing" :disabled="form.processing" @click="save" />
            </div>
        </template>
    </Drawer>
</template>