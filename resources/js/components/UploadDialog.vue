<script setup lang="ts">
import Label from '@/components/ui/label/Label.vue';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from './InputError.vue';
import InputFile from './InputFile.vue';

interface Props {
    visible: boolean;
    templateUrl: string;
    verifyUrl: string;
}

interface Emits {
    (event: 'update:visible', value: boolean): void;
}

interface ImportType {
    value: string;
    label: string;
}

const props = withDefaults(defineProps<Props>(), {
    visible: false,
});

const emits = defineEmits<Emits>();

const visible = computed({
    get: () => props.visible,
    set: (value) => emits('update:visible', value),
});

const form = useForm({
    type: 'INSERT',
    file: null,
});

const importTypes: ImportType[] = [
    { value: 'INSERT', label: 'Insert' },
    { value: 'UPDATE', label: 'Update' },
];

const verify = () => {
    form.post(props.verifyUrl);
};

const afterHidden = () => {
    form.reset();
};
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        dismissable-mask
        :style="{ width: '50vw' }"
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
        @after-hide="afterHidden"
    >
        <div class="grid grid-cols-2 gap-6">
            <div>
                <Label>Jenis Import</Label>
                <Select v-model="form.type" :options="importTypes" option-label="label" option-value="value" fluid />
                <InputError :message="form.errors.type" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                    <span>Download format import </span>
                    <a :href="templateUrl" class="cursor-pointer text-blue-500 hover:underline">disini</a>
                </p>
            </div>

            <div>
                <Label for="file">Jenis Import</Label>
                <InputFile
                    v-model="form.file"
                    accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"
                    @change="form.errors.file = undefined"
                />
                <InputError :message="form.errors.file" />
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-300" id="file_input_help">XLS, XLSX (MAX. 10MB).</p>
            </div>
        </div>

        <template #footer>
            <Button label="Batal" text severity="secondary" @click="visible = false" />
            <Button label="Import" @click="verify" :loading="form.processing" :disabled="form.processing" />
        </template>
    </Dialog>
</template>
