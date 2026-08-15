<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Project } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

type ProjectImportHeader = 'uuid' | 'name' | 'start_date' | 'finish_date' | 'plan_site' | 'plan_cctv' | 'errors';

interface ProjectImportBody extends Project {
    errors: Record<ProjectImportHeader, string[]>;
}

interface Props {
    projects: ProjectImportBody[];
    header: ProjectImportHeader[];
}

const props = defineProps<Props>();

function toTitleCase(str: string, separator: string = ' '): string {
    return str
        .toLowerCase()
        .split(separator)
        .map((word) => {
            return word.charAt(0).toUpperCase() + word.slice(1);
        })
        .join(' ');
}

const importData = () => {
    const filteredProjects = props.projects
        .filter((item) => !Object.keys(item.errors).length)
        .map((project) => ({
            name: project.name,
            start_date: project.start_date,
            finish_date: project.finish_date,
            plan_site: project.plan_site,
            plan_cctv: project.plan_cctv,
        }));

    const form = useForm({
        projects: filteredProjects,
    });

    if (filteredProjects.length !== props.projects.length) {
        Swal.fire({
            icon: 'warning',
            title: `Apakah anda yakin ingin melanjutkan proses?`,
            text: `Total ${filteredProjects.length} data akan diimport`,
            showCancelButton: true,
            confirmButtonText: 'Lanjutkan',
            cancelButtonText: `Batal`,
            customClass: {
                confirmButton: '!bg-primary-500 focus:!ring focus:!ring-primary-300',
            },
        }).then(async (result) => {
            if (result.isConfirmed) {
                form.post(route('project.import'), {
                    preserveScroll: true,
                    onSuccess() {
                        Swal.fire('Sukses', 'Sukses impor data', 'success');
                    },
                });
            }
        });
    }
};
</script>

<template>
    <Head title="Verify Import Project" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Verify Import Project" description="Please verify the import data" />

            <DataTable :value="props.projects" paginator :rows="25" :rowsPerPageOptions="[25, 50, 100, 200]">
                <Column v-for="key in props.header" :key="key" :field="key" :header="toTitleCase(key, '_')" bodyClass="!p-0">
                    <template #body="{ data }">
                        <div class="min-h-12 px-4 py-3" :class="{ 'bg-red-500': data.errors[key]?.length }" v-tooltip="data.errors[key]?.[0]">
                            {{ data[key] }}
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div class="flex items-center justify-end gap-2">
                <Button label="Batal" severity="secondary" @click="router.visit(route('project.index'))" />
                <Button label="Lanjutkan" @click="importData" />
            </div>
        </div>
    </AppLayout>
</template>
