<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <AppLayout title="Halaman Tidak Ditemukan">
        <Head title="404 - Halaman Tidak Ditemukan" />

        <div class="flex min-h-[60vh] flex-col items-center justify-center gap-4 px-4 text-center">
            <UIcon name="i-lucide-file-question" class="size-16 text-muted" />
            <div class="space-y-1">
                <h1 class="text-2xl font-bold">Halaman tidak ditemukan</h1>
                <p class="text-sm text-muted">Halaman yang Anda cari tidak ada, atau Anda tidak memiliki akses untuk melihatnya.</p>
            </div>
            <UButton label="Kembali ke Dashboard" icon="i-lucide-home" :to="route('dashboard')" />
        </div>
    </AppLayout>
</template>
