<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { InertiaForm } from '@inertiajs/vue3';
import { SiteFormNew } from '../type';
import { InputNumberInputEvent } from 'primevue/inputnumber';
import { ref } from 'vue';

interface Props {
    form: InertiaForm<SiteFormNew>;
}

const props = defineProps<Props>();
const tempSiteId = ref('')

const onInputSiteId = (event: InputNumberInputEvent) => {
    const input = (event.originalEvent.target as HTMLInputElement);

    if (input.value.length > 6) {
        input.value = tempSiteId.value
    }
    else {
        tempSiteId.value = input.value
    }
}

const setCctvName = () => {
    if (props.form.site_id && props.form.site_name) {
        for (const [index, cctv] of props.form.cctv.entries()) {
            cctv.cctv_name = cctv.cctv_name
                ? cctv.cctv_name
                : props.form.site_id + '-' + props.form.site_name
                    .replaceAll(/[^\w\s-]/gi, '')
                    .replaceAll(' ', '-')
                    .toUpperCase() + '_CCTV-' + (index + 1).toString().padStart(2, '0')
        }
    }
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <Label for="site_id">Site ID</Label>

        <InputNumber ref="siteIdInput" v-model="form.site_id" input-id="site_id" placeholder="Enter Site ID"
            inputmode="numeric" :use-grouping="false" :max="999999" @input="onInputSiteId"
            @value-change="setCctvName" />

        <InputError :message="form.errors.site_id" />
    </div>
</template>