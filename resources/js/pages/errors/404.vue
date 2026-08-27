<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

const goBack = () => window.history.back();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center gap-4 p-4 text-center">
        <Head title="404 - Halaman Tidak Ditemukan" />

        <UIcon name="i-lucide-file-question" class="size-16 text-muted" />
        <div class="space-y-1">
            <h1 class="text-2xl font-bold">Halaman tidak ditemukan</h1>
            <p class="text-sm text-muted">Halaman yang Anda cari tidak ada atau sudah dipindahkan.</p>
        </div>
        <div class="flex gap-2">
            <UButton label="Kembali" color="neutral" variant="outline" icon="i-lucide-arrow-left" @click="goBack" />
            <UButton label="Ke Halaman Login" icon="i-lucide-log-in" :to="route('login')" />
        </div>
    </div>
</template>
