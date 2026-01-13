<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';

defineProps<{
    status?: number;
}>();

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head title="404 - Page Not Found" />

    <div
        class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 px-4 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"
    >
        <div class="text-center">
            <!-- 404 Illustration -->
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <h1 class="select-none text-[180px] font-black leading-none text-gray-200 sm:text-[240px] dark:text-gray-800">404</h1>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i class="pi pi-exclamation-triangle floating-icon text-6xl text-orange-500 sm:text-8xl"></i>
                    </div>
                </div>
            </div>

            <!-- Error Message -->
            <div class="mb-8 space-y-4">
                <h2 class="text-3xl font-bold text-gray-800 sm:text-4xl dark:text-white">Oops! Page Not Found</h2>
                <p class="mx-auto max-w-md text-lg text-gray-600 dark:text-gray-300">The page you're looking for doesn't exist or has been moved.</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                <Link :href="route('login')">
                    <Button label="Go to Login" icon="pi pi-sign-in" class="w-full shadow-lg sm:w-auto" severity="primary" size="large" />
                </Link>
                <Button label="Go Back" icon="pi pi-arrow-left" @click="goBack" severity="secondary" outlined size="large" class="w-full sm:w-auto" />
            </div>

            <!-- Additional Help -->
            <div class="mt-12">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Need an account?
                    <Link
                        :href="route('register')"
                        class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        Sign Up Here
                    </Link>
                </p>
            </div>

            <!-- Decorative Elements -->
            <div class="mt-16 flex items-center justify-center gap-8 text-gray-300 opacity-50 dark:text-gray-700">
                <i class="pi pi-search text-3xl"></i>
                <i class="pi pi-question-circle text-3xl"></i>
                <i class="pi pi-map-marker text-3xl"></i>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes float {
    0%,
    100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-20px);
    }
}

.floating-icon {
    animation: float 3s ease-in-out infinite;
}
</style>
