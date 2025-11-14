<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import moment from 'moment';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Divider from 'primevue/divider';
import Tag from 'primevue/tag';

interface Props {
    project: {
        id: string;
        name: string;
        description?: string;
        start_date?: string;
        due_date?: string;
        status?: {
            id: string;
            name: string;
            severity?: string;
        };
        priority?: {
            id: string;
            name: string;
            severity?: string;
        };
        created_at?: string;
        updated_at?: string;
    };
}

const props = defineProps<Props>();

// Format tanggal dengan Moment.js
const formatDate = (date: string | undefined) => {
    return date ? moment(date).format('DD MMMM YYYY') : '-';
};

// Navigasi kembali
const goBack = () => {
    router.visit(route('project.index'));
};
</script>

<template>

    <Head :title="`Project Detail - ${props.project.title}`" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <Heading title="Project Detail" description="Detail information about this project" />

            <Card class="shadow-md">
                <template #title>
                    <div class="flex items-center gap-3">
                        <span class="text-3xl">{{ props.project.emoji }}</span>
                        <h2 class="text-xl font-semibold">{{ props.project.title }}</h2>
                    </div>
                </template>

            <!-- Card Detail -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="p-8 gap-4 flex flex-col md:flex-row w-full">
                        <div class="flex flex-col w-1/2 p-4 border rounded-xl">
                            <div class="prose dark:prose-invert max-w-none"
                                v-html="props.project.description || '<p><em>No description</em></p>'" />

                            <Divider />

                            <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div class="space-y-3">
                                    <div>
                                        <p class="mb-1 font-semibold">Status</p>
                                        <Tag :value="props.project.status?.name || '-'"
                                            :severity="props.project.status?.severity" />
                                    </div>

                                    <div>
                                        <p class="mb-1 font-semibold">Priority</p>
                                        <Tag :value="props.project.priority?.name || '-'"
                                            :severity="props.project.priority?.severity" />
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div>
                                        <p class="mb-1 font-semibold">Start Date</p>
                                        <p>{{ moment(props.project.start_date).format('YYYY-MM-DD') }}</p>
                                    </div>

                                    <div>
                                        <p class="mb-1 font-semibold">Due Date</p>
                                        <p>{{ moment(props.project.due_date).format('YYYY-MM-DD') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p class="mb-1 font-semibold">Progress</p>
                                <Tag :value="`${props.project.progress}%`" severity="success"
                                    class="px-3 py-1 text-base" />
                            </div>
                        </div>

                        <div class="flex flex-col w-1/2">
                            <div class="mt-3">
                                <div class="flex flex-row justify-between">
                                    <p class="mb-2 text-lg font-semibold">Project Members</p>
                                    <Button label="Manage Members" icon="pi pi-users"
                                        @click="router.get(route('project.members.members', props.project.id))" />
                                </div>
                                <Divider />

                                <div v-if="props.project.project_members?.length" class="space-y-3">
                                    <div v-for="m in props.project.project_members" :key="m.id"
                                        class="flex justify-between rounded-md border p-3">
                                        <div>
                                            <p class="font-semibold">{{ m.user.name }}</p>
                                            <p class="text-sm opacity-70">{{ m.role?.name }}</p>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="italic text-gray-500">No members assigned.</p>
                            </div>
                        </div>
                    </div>

                    <Divider />
                </template>

                <template #footer>
                    <div class="flex justify-between">
                        <Button label="Back to Projects" icon="pi pi-arrow-left" severity="secondary"
                            @click="router.get(route('project.index'))" />
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
