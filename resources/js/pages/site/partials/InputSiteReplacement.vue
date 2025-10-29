<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { Site } from '@/types';
import { InertiaForm } from '@inertiajs/vue3';
import { SiteFormNew } from '../type';

interface Props {
    dismantleSites: Site[];
    form: InertiaForm<SiteFormNew>;
}

const props = defineProps<Props>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-2" v-if="form.site_category === 'Replacement'">
            <Label for="replacement_to">Site ID Replacement</Label>

            <Select
                v-model="form.replacement_to"
                label-id="replacement_to"
                option-value="uuid"
                :options="props.dismantleSites"
                placeholder="Please Select a Site"
            >
                <template #option="{ option }">
                    {{ option.site_id + '-' + option.site_name }}
                </template>
            </Select>

            <InputError :message="form.errors.replacement_to" />
        </div>

        <div class="flex flex-col gap-2" v-if="form.site_category === 'Replacement'">
            <Label for="replacement_to">Site Name Replacement</Label>
            <InputText placeholder="Site Name Replacement" disabled />
        </div>
    </div>
</template>
