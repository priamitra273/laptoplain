<script setup>
import { router } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Dropdown from 'primevue/dropdown';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    modelValue: Boolean,
    task: { type: Object, default: () => ({}) },
    statuses: Array,
    priorities: Array,
    types: Array,
    projectEncoded: String,
});

const emit = defineEmits(['update:modelValue']);

const form = ref({
    title: '',
    description: '',
    status_id: null,
    priority_id: null,
    type_id: null,
    parent_id: null,
});

watch(
    () => props.task,
    (val) => {
        form.value = {
            title: val?.title ?? '',
            description: val?.description ?? '',
            status_id: val?.status_id ?? null,
            priority_id: val?.priority_id ?? null,
            type_id: val?.type_id ?? null,
            parent_id: val?.parent_id ?? null,
        };
    },
    { immediate: true },
);

const isEdit = computed(() => !!props.task?.id);

function save() {
    if (isEdit.value) {
        router.put(
            route('project.tasks.update', {
                projectEncoded: props.projectEncoded,
                taskEncoded: props.task.encoded,
            }),
            form.value,
            {
                onSuccess: () => emit('update:modelValue', false),
            },
        );
    } else {
        router.post(route('project.tasks.store', props.projectEncoded), form.value, {
            onSuccess: () => emit('update:modelValue', false),
        });
    }
}
</script>

<template>
    <Dialog
        :visible="modelValue"
        @update:visible="emit('update:modelValue', $event)"
        :header="isEdit ? 'Edit Task' : 'Create Task'"
        modal
        class="w-2/3"
    >
        <div class="flex flex-col gap-3">
            <label>Title</label>
            <InputText v-model="form.title" class="w-full" />

            <label>Description</label>
            <Textarea v-model="form.description" class="w-full" rows="4" />

            <label>Status</label>
            <Dropdown v-model="form.status_id" :options="statuses" optionLabel="name" optionValue="id" class="w-full" />

            <label>Priority</label>
            <Dropdown v-model="form.priority_id" :options="priorities" optionLabel="name" optionValue="id" class="w-full" />

            <label>Type</label>
            <Dropdown v-model="form.type_id" :options="types" optionLabel="name" optionValue="id" class="w-full" />

            <div class="mt-4 flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="emit('update:modelValue', false)" />
                <Button label="Save" @click="save" />
            </div>
        </div>
    </Dialog>
</template>
