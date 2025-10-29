<script setup lang="ts">
import AdvanceFileUpload from '@/components/AdvanceFileUpload.vue';
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { SiteStatus } from '@/pages/site/type';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';
import collect from 'collect.js'

interface Props {
    siteUuid: String;
    statuses: SiteStatus[];
}

interface Form {
    _method: 'PUT';
    site_status_uuid: string|null;
    remark: string;
    attachments: (string | File)[];
    [key: string]: any;
}

const props = defineProps<Props>()

const form: InertiaForm<Form> = useForm({
    _method: "PUT",
    site_status_uuid: null,
    remark: '',
    attachments: []
});

const errors = computed(() => {
    return collect(form.errors).undot().all()
})

const statusSeverities: Record<string, string> = {
    OPEN: 'primary',
    PROGRESS: 'warn',
    RELOCATION: 'warn',
    DISMANTLE: 'danger',
    COMPLETE: 'success'
}

const status = computed(() => {
    return props.statuses.find((item) => item.uuid === form.site_status_uuid)?.name ?? '';
});

const processing = computed(() => form.processing)

function save() {
    form.post(route('installation.update', { installation: props.siteUuid }), {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
        }
    })
}

watch(() => form.attachments.length, () => {
    delete form.errors.attachments
})

defineExpose({
    save,
    loading: processing
})
</script>

<template>
    <form class="grid gap-4 md:grid-cols-2" @submit.prevent="save">
        <div class="flex flex-col gap-2">
            <Label>Status</Label>
            <Select v-model="form.site_status_uuid" :options="props.statuses" option-value="uuid" option-label="name"
                placeholder="Select a Status">
                <template #value="{ value, placeholder }">
                    <Tag v-if="value" :severity="statusSeverities[status]" :value="status" />
                    <span v-else>{{ placeholder }}</span>
                </template>

                <template #option="{ option }">
                    <Tag :severity="statusSeverities[option.name]" :value="option.name" />
                </template>
            </Select>
            <InputError :message="form.errors.site_status_uuid" />
        </div>

        <div class="col-span-2 flex flex-col gap-2">
            <Label for="longitude">Remark</Label>
            <Editor v-model="form.remark" editorStyle="height: 100px">
                <template v-slot:toolbar>
                    <span class="ql-formats">
                        <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                        <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                        <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                    </span>

                    <span class="ql-formats">
                        <button class="ql-list" value="ordered" v-tooltip.bottom="'Ordered'" type="button"></button>
                        <button class="ql-list" value="bullet" v-tooltip.bottom="'Bullet'" type="button"></button>
                    </span>
                </template>
            </Editor>
            <InputError :message="form.errors.remark" />
        </div>

        <div class="col-span-2 flex flex-col gap-2">
            <Label for="longitude">Attachments</Label>
            <AdvanceFileUpload v-model="form.attachments" multiple />
            <InputError :message="errors.attachments" />
        </div>
    </form>
</template>