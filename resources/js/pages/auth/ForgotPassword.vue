<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import bgImage from '@/images/Bg.jpg';
import Logo from '@/images/logo-dark.png';
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-surface-100">
        <div class="grid min-h-screen md:grid-cols-2 lg:grid-cols-5">

            <div class="relative hidden overflow-hidden md:block lg:col-span-2">
                <img
                    :src="bgImage"
                    alt="Background"
                    class="absolute inset-0 h-full w-full scale-105 object-cover object-center"
                />

                <div class="absolute inset-0 bg-gradient-to-br from-[#536976]/80 to-[#292E49]/90"></div>

                <div class="relative z-10 flex h-full flex-col items-center justify-center p-6 md:p-8 lg:p-10">
                    <img :src="Logo" alt="Logo" class="mb-8 h-20 w-auto md:mb-10 md:h-24 lg:mb-12 lg:h-32" />
                    <div class="text-center">
                        <h1 class="font-serif text-2xl leading-tight text-white md:text-2xl lg:text-3xl">
                            Reset Your Password
                        </h1>
                        <p class="mt-2 max-w-sm px-4 text-sm text-white/80 md:px-0">
                            Enter your email and we'll send you instructions to reset your password.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center p-6 md:p-8 lg:col-span-3 lg:p-12">
                <Head title="Forgot Password" />

                <form class="w-full max-w-md" @submit.prevent="submit">
                    
                    <div class="mb-6 text-left md:mb-8">
                        <h2 class="mb-1 font-serif text-2xl text-surface-700 md:text-3xl">
                            Forgot Password
                        </h2>
                        <span class="text-sm text-surface-500 md:text-base">
                            Enter your email to receive reset instructions
                        </span>
                    </div>

                    <div class="space-y-4 md:space-y-5">

                        <div v-if="status" class="rounded-md bg-green-50 p-3">
                            <p class="text-center text-sm font-medium text-green-600">
                                {{ status }}
                            </p>
                        </div>

                        <div class="text-left">
                            <label for="email" class="mb-2 block text-sm font-medium text-surface-600">
                                Email address
                            </label>

                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="email@example.com"
                                class="w-full"
                                required
                                autofocus
                                autocomplete="email"
                                :invalid="!!form.errors.email"
                            />

                            <InputError
                                :message="form.errors.email"
                                class="mt-1.5 text-sm text-red-600"
                            />
                        </div>

                        <div class="pt-2">
                            <Button
                                type="submit"
                                label="Send Reset Link"
                                class="w-full"
                                :loading="form.processing"
                                :disabled="form.processing"
                            />
                        </div>

                        <div class="text-center text-sm text-surface-500">
                            <span>Or, return to </span>
                            <TextLink :href="route('login')" class="text-primary hover:underline">
                                Log in
                            </TextLink>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
