<script setup>
import { useLayout } from '@/composables/useLayouts';
import { Link, router } from '@inertiajs/vue3';
const { onMenuToggle, onConfigSidebarToggle } = useLayout();

async function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-start">
            <Link class="layout-topbar-logo" href="/">
                <img src="/storage/logo.png" />
            </Link>
            <a ref="menuButton" class="layout-menu-button" @click="onMenuToggle">
                <i class="pi pi-angle-right"></i>
            </a>
        </div>

        <div class="layout-topbar-end">
            <div class="layout-topbar-actions-end">
                <ul class="layout-topbar-items">
                    <!-- <li class="layout-topbar-search">
                        <input type="text" placeholder="Search" />
                        <i class="pi-fw pi pi-search"></i>
                    </li> -->

                    <li>
                        <button
                            v-styleclass="{
                                selector: '@next',
                                enterFromClass: 'hidden',
                                enterActiveClass: 'animate-scalein',
                                leaveToClass: 'hidden',
                                leaveActiveClass: 'animate-fadeout',
                                hideOnOutsideClick: true,
                            }"
                        >
                            <i class="pi pi-bell"></i>
                        </button>
                        <div class="hidden">
                            <ul class="m-0 list-none p-0">
                                <li>
                                    <a class="flex cursor-pointer gap-2 px-4 py-2 text-color hover:text-primary">
                                        <i class="pi pi-fw pi-sliders-h text-lg"></i>
                                        <span>Pending tasks</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="flex cursor-pointer gap-2 px-4 py-2 text-color hover:text-primary">
                                        <i class="pi pi-fw pi-calendar text-lg"></i>
                                        <span>Meeting today at 3pm</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="flex cursor-pointer gap-2 px-4 py-2 text-color hover:text-primary">
                                        <i class="pi pi-fw pi-download text-lg"></i>
                                        <span>Download documents</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="flex cursor-pointer gap-2 px-4 py-2 text-color hover:text-primary">
                                        <i class="pi pi-fw pi-bookmark text-lg"></i>
                                        <span>Book flight</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- <li>
                        <button type="button" v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'animate-scalein', leaveToClass: 'hidden', leaveActiveClass: 'animate-fadeout', hideOnOutsideClick: true }">
                            <i class="pi pi-envelope"></i>
                        </button>
                        <div class="hidden">
                            <ul class="list-none p-0 m-0 flex flex-col text-color">
                                <li>
                                    <a class="cursor-pointer flex items-center px-4 py-2 gap-4 hover:text-primary">
                                        <img src="/storage/layout/images/avatar/avatar5.png" class="w-12 h-12" />
                                        <span>Give me a call</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="cursor-pointer flex items-center px-4 py-2 gap-4 hover:text-primary">
                                        <img src="/storage/layout/images/avatar/avatar1.png" class="w-12 h-12" />
                                        <span>Sales reports attached</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="cursor-pointer flex items-center px-4 py-2 gap-4 hover:text-primary">
                                        <img src="/storage/layout/images/avatar/avatar2.png" class="w-12 h-12" />
                                        <span>About your invoice</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="cursor-pointer flex items-center px-4 py-2 gap-4 hover:text-primary">
                                        <img src="/storage/layout/images/avatar/avatar3.png" class="w-12 h-12" />
                                        <span>Meeting today at 10pm</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="cursor-pointer flex items-center px-4 py-2 gap-4 hover:text-primary">
                                        <img src="/storage/layout/images/avatar/avatar4.png" class="w-12 h-12" />
                                        <span>Out of office</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li> -->

                    <li>
                        <button
                            v-styleclass="{
                                selector: '@next',
                                enterFromClass: 'hidden',
                                enterActiveClass: 'animate-scalein',
                                leaveToClass: 'hidden',
                                leaveActiveClass: 'animate-fadeout',
                                hideOnOutsideClick: true,
                            }"
                        >
                            <i class="pi pi-user"></i>
                        </button>
                        <div class="hidden">
                            <ul class="m-0 list-none p-0">
                                <li>
                                    <Link href="settings" class="flex cursor-pointer gap-2 px-4 py-2 text-color hover:text-primary">
                                        <i class="pi pi-fw pi-sliders-h text-lg"></i>
                                        <span>Settings</span>
                                    </Link>
                                </li>
                                <li>
                                    <a class="flex cursor-pointer gap-2 px-4 py-2 text-color hover:text-primary" @click="logout">
                                        <i class="pi pi-fw pi-sign-out text-lg"></i>
                                        <span>Logout</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li>
                        <button type="button" class="layout-config-button" @click="onConfigSidebarToggle">
                            <i class="pi pi-palette"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
