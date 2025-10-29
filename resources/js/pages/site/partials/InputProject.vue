<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Label from '@/components/ui/label/Label.vue';
import { Project } from '@/types';
import { InertiaForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { SiteFormNew } from '../type';

interface Props {
    projects: Project[];
    form: InertiaForm<SiteFormNew>;
}

const props = defineProps<Props>();

const siteCategories = ref<string[]>(['New', 'Replacement']);
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="flex flex-col gap-2">
            <Label for="project_id">Project ID</Label>
            <Select
                v-model="form.project_id"
                label-id="project_id"
                option-value="uuid"
                option-label="name"
                :options="props.projects"
                placeholder="Please Select a Project"
            >
            </Select>
            <InputError :message="form.errors.project_id" />
        </div>

        <div class="flex flex-col gap-2">
            <Label>Category</Label>
            <SelectButton v-model="form.site_category" :options="siteCategories" :allow-empty="false" />
            <InputError :message="form.errors.site_category" />
        </div>
    </div>
</template>
