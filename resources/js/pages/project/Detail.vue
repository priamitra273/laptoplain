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
    <Head title="Detail Project" />

    <AppLayout>
        <div class="flex flex-col gap-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <Heading title="Detail Project" description="Informasi lengkap mengenai project" />
                <Button icon="pi pi-arrow-left" label="Kembali" class="p-button-sm p-button-secondary" @click="goBack" />
            </div>

            <!-- Card Detail -->
            <Card class="shadow-sm">
                <template #content>
                    <div class="space-y-6 text-sm text-gray-700">
                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <p class="font-semibold">Status</p>
                                <Tag :value="props.project.status?.name || '-'" :severity="props.project.status?.severity || 'info'" class="mt-1" />
                            </div>

                            <div>
                                <p class="font-semibold">Prioritas</p>
                                <Tag
                                    :value="props.project.priority?.name || '-'"
                                    :severity="props.project.priority?.severity || 'info'"
                                    class="mt-1"
                                />
                            </div>

                            <div>
                                <p class="font-semibold">Tanggal Mulai</p>
                                <p class="mt-1">{{ formatDate(props.project.start_date) }}</p>
                            </div>

                            <div>
                                <p class="font-semibold">Tanggal Selesai</p>
                                <p class="mt-1">{{ formatDate(props.project.due_date) }}</p>
                            </div>
                        </div>

                        <Divider />

                        <!-- Deskripsi -->
                        <div>
                            <p class="mb-2 font-semibold">Deskripsi</p>
                            <div
                                v-if="props.project.description"
                                class="prose prose-sm max-w-none text-gray-700"
                                v-html="props.project.description"
                            ></div>
                            <p v-else class="italic text-gray-500">Tidak ada deskripsi.</p>
                        </div>

                        <Divider />

                        <!-- Metadata -->
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <p class="font-semibold">Dibuat</p>
                                <p class="mt-1">{{ formatDate(props.project.created_at) }}</p>
                            </div>
                            <div>
                                <p class="font-semibold">Diperbarui</p>
                                <p class="mt-1">{{ formatDate(props.project.updated_at) }}</p>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
