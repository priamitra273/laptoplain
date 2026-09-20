<script setup lang="ts">
import bgImage from '@/images/Bg.jpg';
import logo from '@/images/logo-dark.png';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: 'Welcome Back',
        description: 'Securely access your dashboard and manage everything in one place.',
    },
);
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-5">
        <div class="relative hidden overflow-hidden lg:col-span-2 lg:block">
            <img :src="bgImage" alt="" class="absolute inset-0 size-full scale-105 object-cover object-center" />
            <div class="absolute inset-0 bg-linear-to-br from-[#536976]/80 to-[#292E49]/95" />

            <div class="relative z-10 flex h-full flex-col items-center justify-center gap-10 p-10">
                <img :src="logo" alt="" class="h-25 w-auto" />

                <div class="max-w-sm space-y-2 text-center">
                    <h1 class="font-serif text-lg leading-tight text-white md:text-lg lg:text-xl">{{ title }}</h1>
                    <p class="mt-2 max-w-xs px-4 text-xs text-white/80 md:px-0">{{ description }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center bg-default p-6 lg:col-span-3 lg:p-12">
            <div class="w-full max-w-md">
                <slot />
            </div>
        </div>
    </div>
</template>
