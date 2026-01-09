<script setup lang="ts">
import AppLayout from '@/layouts/avalon/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
</script>

<template>
    <Head title="Page Not Found" />

    <AppLayout>
        <div class="flex min-h-[calc(100vh-200px)] items-center justify-center p-6">
            <Card class="w-full max-w-md shadow-lg">
                <template #content>
                    <div class="space-y-6 text-center">
                        <!-- Icon -->
                        <div class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/20">
                            <i class="pi pi-exclamation-triangle text-5xl text-red-500"></i>
                        </div>

                        <!-- Title -->
                        <div>
                            <h1 class="mb-2 text-6xl font-bold text-gray-800 dark:text-gray-200">404</h1>
                            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300">Page Not Found</h2>
                        </div>

                        <!-- Description -->
                        <p class="text-gray-600 dark:text-gray-400">
                            Sorry, the page you are looking for could not be found or you don't have permission to access it.
                        </p>

                        <!-- Actions -->
                        <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
                            <Link :href="route('dashboard')">
                                <Button label="Go to Dashboard" icon="pi pi-home" severity="primary" class="w-full sm:w-auto" />
                            </Link>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
.space-y-6 > * + * {
    margin-top: 1.5rem;
}

.space-y-3 > * + * {
    margin-top: 0.75rem;
}
</style>
