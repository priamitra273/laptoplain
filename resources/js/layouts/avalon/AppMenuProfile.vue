<script setup>
import { useLayout } from '@/composables/useLayouts';
import { router } from '@inertiajs/vue3'
import { computed } from 'vue';

const { layoutState, layoutConfig, onMenuProfileToggle, isHorizontal, isSlim } = useLayout();
const menuClass = computed(() => (isHorizontal.value ? 'overlay' : null));

function navigateTo(route) {
    router.push(route);
    toggleMenu();
}

function toggleMenu() {
    const menu = document.querySelector('.menu-transition');

    if (layoutState.menuProfileActive) {
        menu.style.maxHeight = '0';
        menu.style.opacity = '0';
        if (isHorizontal.value) {
            menu.style.transform = 'scaleY(0.8)';
        }
    } else {
        menu.style.maxHeight = menu.scrollHeight + 'px';
        menu.style.opacity = '1';
        if (isHorizontal.value) {
            menu.style.transform = 'scaleY(1)';
        }
    }
    onMenuProfileToggle();
}

const iconClass = computed(() => {
    const profilePositionStart = layoutConfig.menuProfilePosition === 'start';

    return {
        'pi-angle-up': (layoutState.menuProfileActive && (profilePositionStart || isHorizontal.value)) || (!layoutState.menuProfileActive && !profilePositionStart && !isHorizontal.value),
        'pi-angle-down': (!layoutState.menuProfileActive && profilePositionStart) || (layoutState.menuProfileActive && !profilePositionStart) || isHorizontal.value
    };
});

function tooltipValue(tooltipText) {
    return isSlim.value ? tooltipText : null;
}
</script>

<template>
    <div class="layout-menu-profile">
        <button v-tooltip="{ value: tooltipValue('Profile') }" class="rounded-none" @click="toggleMenu()">
            <img src="/storage/layout/images/avatar/amyelsner.png" alt="avatar" style="width: 32px; height: 32px" />
            <span>
                <strong>Amy Elsner</strong>
                <small>Webmaster</small>
            </span>
            <i class="layout-menu-profile-toggler pi pi-fw" :class="iconClass"></i>
        </button>

        <ul :class="['menu-transition', menuClass]" style="max-height: 0; opacity: 0">
            <li v-tooltip="{ value: tooltipValue('Settings') }">
                <button @click="navigateTo('/profile/create')">
                    <i class="pi pi-cog pi-fw"></i>
                    <span>Settings</span>
                </button>
            </li>

            <li v-tooltip="{ value: tooltipValue('Profile') }">
                <button @click="navigateTo('/profile/list')">
                    <i class="pi pi-file-o pi-fw"></i>
                    <span>Profile</span>
                </button>
            </li>
            <li v-tooltip="{ value: tooltipValue('Support') }">
                <button @click="navigateTo('/documentation')">
                    <i class="pi pi-compass pi-fw"></i>
                    <span>Support</span>
                </button>
            </li>
            <li v-tooltip="{ value: tooltipValue('Logout') }">
                <button @click="navigateTo('/auth/login2')">
                    <i class="pi pi-power-off pi-fw"></i>
                    <span>Logout</span>
                </button>
            </li>
        </ul>
    </div>
</template>

<style scoped>
.menu-transition {
    transition:
        max-height 400ms cubic-bezier(0.86, 0, 0.07, 1),
        opacity 400ms cubic-bezier(0.86, 0, 0.07, 1);
}
.menu-transition.overlay {
    transition:
        opacity 100ms linear,
        transform 120ms cubic-bezier(0, 0, 0.2, 1);
}
</style>
