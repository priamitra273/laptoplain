<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import bgImage from '@/images/Bg.jpg';
import Logo from '@/images/logo-dark.png';
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-surface-100">
        <div class="grid min-h-screen md:grid-cols-2 lg:grid-cols-5">
            <!-- LEFT IMAGE SECTION -->
            <div class="relative hidden overflow-hidden md:block lg:col-span-2">
                <!-- Background Image -->
                <img :src="bgImage" alt="Background" class="absolute inset-0 h-full w-full scale-105 object-cover object-center" />

                <!-- Overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-[#536976]/80 to-[#292E49]/90"></div>

                <div class="relative z-10 flex h-full flex-col items-center justify-center p-6 md:p-8 lg:p-10">
                    <img :src="Logo" alt="Logo" class="mb-8 h-20 w-auto md:mb-10 md:h-24 lg:mb-12 lg:h-32" />
                    <div class="text-center">
                        <h1 class="font-serif text-2xl leading-tight text-white md:text-2xl lg:text-3xl">Join Us Today</h1>
                        <p class="mt-2 max-w-sm px-4 text-sm text-white/80 md:px-0">
                            Create your account and start managing your projects efficiently.
                        </p>
                    </div>
                </div>
            </div>

            <!-- RIGHT FORM SECTION -->
            <div class="flex items-center justify-center p-6 md:p-8 lg:col-span-3 lg:p-12">
                <Head title="Register" />

                <form class="w-full max-w-md" @submit.prevent="submit">
                    <!-- Heading -->
                    <div class="mb-6 text-left md:mb-8">
                        <h2 class="mb-1 font-serif text-2xl text-surface-700 md:text-3xl">Create Account</h2>
                        <span class="text-sm text-surface-500 md:text-base"> Enter your details below to create your account </span>
                    </div>

                    <div class="space-y-4 md:space-y-5">
                        <!-- Full Name -->
                        <div class="text-left">
                            <label for="name" class="mb-2 block text-sm font-medium text-surface-600"> Full name </label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Full name"
                                class="w-full"
                                required
                                autofocus
                                :tabindex="1"
                                autocomplete="name"
                                :invalid="!!form.errors.name"
                            />
                            <InputError :message="form.errors.name" class="mt-1.5 text-sm text-red-600" />
                        </div>

                        <!-- Email -->
                        <div class="text-left">
                            <label for="email" class="mb-2 block text-sm font-medium text-surface-600"> Email address </label>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="email@example.com"
                                class="w-full"
                                required
                                :tabindex="2"
                                autocomplete="email"
                                :invalid="!!form.errors.email"
                            />
                            <InputError :message="form.errors.email" class="mt-1.5 text-sm text-red-600" />
                        </div>

                        <!-- Password -->
                        <div class="text-left">
                            <label for="password" class="mb-2 block text-sm font-medium text-surface-600"> Password </label>
                            <InputText
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="Password"
                                class="w-full"
                                required
                                :tabindex="3"
                                autocomplete="new-password"
                                :invalid="!!form.errors.password"
                            />
                            <InputError :message="form.errors.password" class="mt-1.5 text-sm text-red-600" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="text-left">
                            <label for="password_confirmation" class="mb-2 block text-sm font-medium text-surface-600"> Confirm password </label>
                            <InputText
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Confirm password"
                                class="w-full"
                                required
                                :tabindex="4"
                                autocomplete="new-password"
                                :invalid="!!form.errors.password_confirmation"
                            />
                            <InputError :message="form.errors.password_confirmation" class="mt-1.5 text-sm text-red-600" />
                        </div>

                        <!-- Submit -->
                        <div class="pt-2">
                            <Button
                                type="submit"
                                label="Create account"
                                class="w-full"
                                :tabindex="5"
                                :loading="form.processing"
                                :disabled="form.processing"
                            />
                        </div>

                        <!-- Login Link -->
                        <div class="text-center text-sm text-surface-600">
                            Already have an account?
                            <TextLink :href="route('login')" :tabindex="6" class="font-medium text-primary hover:underline"> Log in </TextLink>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
