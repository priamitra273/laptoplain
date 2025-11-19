<script setup>
import { ref, watch } from 'vue';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Textarea from 'primevue/textarea';
import Checkbox from 'primevue/checkbox';

const props = defineProps({
    modelValue: Boolean,
    statuses: Array,
    priorities: Array,
    types: Array,
    task: { type: Object, default: () => ({}) }
});

const emit = defineEmits(['update:modelValue', 'submit']);

const form = ref({
    title: '',
    start_date: null,
    due_date: null,
    is_archive: false,
    status_id: null,
    priority_id: null,
    type_id: null,
    description: '',
    parent_id: null,
});

watch(
    () => props.task,
    (val) => {
        form.value = { ...form.value, ...val };
    },
    { immediate: true }
);
</script>

<template>
    <Dialog
        modal
        :visible="modelValue"
        @update:visible="val => emit('update:modelValue', val)"
        header="Task Form"
        :style="{ width: '35rem' }"
    >
        <div class="flex flex-col gap-3">
            <div>
                <label>Title</label>
                <InputText v-model="form.title" class="w-full" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label>Start Date</label>
                    <InputText type="date" v-model="form.start_date" class="w-full" />
                </div>

                <div>
                    <label>Due Date</label>
                    <InputText type="date" v-model="form.due_date" class="w-full" />
                </div>
            </div>

            <div>
                <label>Status</label>
                <Dropdown v-model="form.status_id" :options="statuses" optionLabel="name" optionValue="id" class="w-full" />
            </div>

            <div>
                <label>Priority</label>
                <Dropdown v-model="form.priority_id" :options="priorities" optionLabel="name" optionValue="id" class="w-full" />
            </div>

            <div>
                <label>Type</label>
                <Dropdown v-model="form.type_id" :options="types" optionLabel="name" optionValue="id" class="w-full" />
            </div>

            <div>
                <label>Description</label>
                <Textarea v-model="form.description" class="w-full" rows="4" />
            </div>

            <div class="flex items-center gap-2 mt-2">
                <Checkbox v-model="form.is_archive" binary />
                <label>Archived?</label>
            </div>

            <div class="flex justify-end mt-3">
                <Button label="Cancel" text @click="emit('update:modelValue', false)" />
                <Button label="Save" class="ml-2" @click="emit('submit', form)" />
            </div>
        </div>
    </Dialog>
</template>
