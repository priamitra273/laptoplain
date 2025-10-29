<script setup lang="ts">
import SiteMap from '@/components/SiteMap.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { AnalyticServer, Department } from '@/types';
import { Head, InertiaForm, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { watch } from 'vue';
import CctvSection from './partials/CctvSection.vue';
import EmbedSection from './partials/EmbedSection.vue';
import SetupSection from './partials/SetupSection.vue';
import { Analytic, Category, Form } from './type';

interface Props {
    analytic: Analytic;
    categories: Category[];
    departments: Department[];
    servers: AnalyticServer[];
}

const props = defineProps<Props>();

const form: InertiaForm<Form> = useForm({
    category_uuid: props.analytic.category_uuid ?? null,
    department_uuid: props.analytic.department_uuid ?? null,
    link_embed_bb: props.analytic.link_embed_bb ?? null,
    server_uuid: props.analytic.server_uuid ?? null,
    polygon: props.analytic.polygon ?? [],
    threshold: props.analytic.threshold ?? null,
});

const save = () => {
    form.put(route('analytic.update', props.analytic.uuid), {
        preserveScroll: true,
        onSuccess() {
            Swal.fire('Success', 'Successfully save data', 'success');
        },
    });
};

const incompleteWarning = (): void => {
    Swal.fire({
        icon: 'warning',
        title: `Are you sure?`,
        text: 'You have not completed the form, this will saved as a draft!',
        showCancelButton: true,
        confirmButtonText: 'Save',
        cancelButtonText: `Cancel`,
    }).then(async (result) => {
        if (result.isConfirmed) {
            save();
        }
    });
}

const saveConfirmation = (): void => {
    if (!form.category_uuid) {
        incompleteWarning();
        return;
    }

    const category = props.categories.find((item) => item.uuid === form.category_uuid);
    
    const isComplete = form.category_uuid && form.department_uuid && form.link_embed_bb && form.server_uuid

    if (isComplete) {
        if (category?.has_polygon || category?.has_threshold) {
            if (!form.polygon || !form.threshold) {
                incompleteWarning();
                return;
            }

            if (form.threshold) {
                for (const key in form.threshold) {
                    if (!form.threshold[key]) {
                        incompleteWarning();
                        return;
                    }
                }
            }
        }
        
        save();
        return;
    }

    incompleteWarning();
}

for (const key in form.data()) {
    watch(
        () => form[key],
        () => {
            delete form.errors[key];
        },
    );
}
</script>

<template>
    <Head title="Setup Streaming CCTV" />

    <AppLayout>
        <form class="grid grid-cols-2 gap-6" @submit.prevent="saveConfirmation">
            <CctvSection :analytic="analytic" />
            <SiteMap :latitude="analytic.latitude" :longitude="analytic.longitude" variant="marker" />
            <EmbedSection :analytic="analytic" :form="form" class="col-span-2" />

            <SetupSection :analytic="analytic" :form="form" :departments="departments" :categories="categories" :servers="servers" />

            <div class="flex justify-end gap-3 md:col-span-2">
                <Button label="Back" severity="secondary" @click="router.visit(route('analytic.index'))" />
                <Button label="Submit" type="submit" :loading="form.processing" :disabled="form.processing" @click="saveConfirmation" />
            </div>
        </form>
    </AppLayout>
</template>
