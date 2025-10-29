<script setup>
import { useLayout } from '@/composables/useLayouts';
import { calculateScrollbarWidth } from '@primeuix/utils/dom';
import { nextTick, onBeforeMount, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

const { layoutConfig, layoutState, setActiveMenuItem, setMenuStates, setStaticMenuMobile, onMenuToggle, isHorizontal, isSlim, isSlimPlus, isDesktop } = useLayout();

const props = defineProps({
    item: {
        type: Object,
        default: () => ({})
    },
    index: {
        type: Number,
        default: 0
    },
    root: {
        type: Boolean,
        default: true
    },
    parentItemKey: {
        type: String,
        default: null
    }
});

const isActiveMenu = ref(false);
const itemKey = ref(null);
const subMenuRef = ref(null);
const menuItemRef = ref(null);

onBeforeMount(() => {
    itemKey.value = props.parentItemKey ? props.parentItemKey + '-' + props.index : String(props.index);

    const activeItem = layoutState.activeMenuItem;

    isActiveMenu.value = activeItem === itemKey.value || activeItem ? activeItem.startsWith(itemKey.value + '-') : false;
    handleRouteChange(route().current());
});

watch(
    () => isActiveMenu.value,
    () => {
        const rootIndex = props.root ? props.index : parseInt(`${props.parentItemKey}`[0], 10);
        const overlay = document.querySelectorAll('.layout-root-submenulist')[rootIndex];
        const target = document.querySelectorAll('.layout-root-menuitem')[rootIndex];

        if ((isSlim.value || isSlimPlus.value || isHorizontal.value) && isDesktop) {
            nextTick(() => {
                calculatePosition(overlay, target);
            });
        }
    }
);

watch(
    () => layoutState.activeMenuItem,
    (newVal) => {
        isActiveMenu.value = newVal === itemKey.value || newVal.startsWith(itemKey.value + '-');
    }
);

watch(
    () => layoutConfig.menuMode,
    () => {
        isActiveMenu.value = false;
    }
);

watch(
    () => layoutState.overlaySubmenuActive,
    (newValue) => {
        if (!newValue) {
            isActiveMenu.value = false;
        }
    }
);

function handleRouteChange(newPath) {
    // console.log({
    //     key: itemKey.value,
    //     path: newPath,
    //     href: props.item.to,
    //     config: layoutConfig,
    //     isSlim: isSlim.value,
    //     isSlimPlus: isSlimPlus.value,
    //     isHorizontal: isHorizontal.value,
    //     status: (!(isSlim.value || isSlimPlus.value || isHorizontal.value) && props.item.to && props.item.to === newPath)  
    // })

    if (!(isSlim.value || isSlimPlus.value || isHorizontal.value) && props.item.to && checkActiveRoute(props.item)) {
        isActiveMenu.value = true;
        setActiveMenuItem(itemKey);
    } else if (isSlim.value || isSlimPlus.value || isHorizontal.value) {
        isActiveMenu.value = false;
    }
}

watch(() => route().current(), handleRouteChange);

async function itemClick(event, item) {
    if (item.disabled) {
        event.preventDefault();
        return;
    }

    if ((item.to || item.url) && (layoutState.staticMenuMobileActive || layoutState.overlayMenuActive)) {
        onMenuToggle();
    }

    if (item.command) {
        item.command({ originalEvent: event, item: item });
    }

    if (item.items) {
        if (props.root && isActiveMenu.value && (isSlim.value || isSlimPlus.value || isHorizontal.value)) {
            setMenuStates(false);

            return;
        }

        setActiveMenuItem(isActiveMenu.value ? props.parentItemKey : itemKey);

        if (props.root && !isActiveMenu.value && (isSlim.value || isSlimPlus.value || isHorizontal.value)) {
            setMenuStates(true);
            isActiveMenu.value = true;

            removeAllTooltips();
        }
    } else {
        if (!isDesktop) {
            setStaticMenuMobile();
        }

        if (isSlim.value || isSlimPlus.value || isHorizontal.value) {
            setMenuStates(false);

            return;
        }

        setActiveMenuItem(itemKey);
    }
}

function onMouseEnter() {
    if (props.root && (isSlim.value || isSlimPlus.value || isHorizontal.value) && isDesktop) {
        if (!isActiveMenu.value && layoutState.menuHoverActive) {
            setActiveMenuItem(itemKey);
        }
    }
}

function removeAllTooltips() {
    const tooltips = document.querySelectorAll('.p-tooltip');
    tooltips.forEach((tooltip) => {
        tooltip.remove();
    });
}

function calculatePosition(overlay, target) {
    if (overlay) {
        const { left, top } = target.getBoundingClientRect();
        const [vWidth, vHeight] = [window.innerWidth, window.innerHeight];
        const [oWidth, oHeight] = [overlay.offsetWidth, overlay.offsetHeight];
        const scrollbarWidth = calculateScrollbarWidth();
        const topbarEl = document.querySelector('.layout-topbar');
        const topbarHeight = topbarEl?.offsetHeight || 0;

        // reset
        overlay.style.top = overlay.style.left = '';

        if (isHorizontal.value) {
            const width = left + oWidth + scrollbarWidth;
            overlay.style.left = vWidth < width ? `${left - (width - vWidth)}px` : `${left}px`;
        } else if (isSlim.value || isSlimPlus.value) {
            const topOffset = top - topbarHeight;
            const height = topOffset + oHeight + topbarHeight;
            overlay.style.top = vHeight < height ? `${topOffset - (height - vHeight)}px` : `${topOffset}px`;
        }
    }
}

function checkActiveRoute(item) {
    return route().current(item.to.includes('.') ? item.to.replace(/(\.[^.]+)$/, `.*`) : item.to)
}

onMounted(() => {
    handleRouteChange(route().current())
})
</script>

<template>
    <li ref="menuItemRef" :class="{ 'layout-root-menuitem': root, 'active-menuitem': isActiveMenu }">
        <div v-if="root && item.visible !== false" class="layout-menuitem-root-text">
            <span>{{ item.label }}</span> <i class="layout-menuitem-root-icon pi pi-fw pi-ellipsis-h"></i>
        </div>
        <a
            v-if="(!item.to || item.items) && item.visible !== false"
            :href="item.url"
            @click="itemClick($event, item, index)"
            :class="item.class"
            :target="item.target"
            tabindex="0"
            @mouseenter="onMouseEnter"
            v-tooltip.hover="isSlim && root && !isActiveMenu ? item.label : null"
        >
            <!-- <i :class="item.icon" class="layout-menuitem-icon"></i> -->
            <Icon :name="item.icon" class="layout-menuitem-icon"/>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items"></i>
        </a>
        <Link
            v-if="item.to && !item.items && item.visible !== false"
            @click="itemClick($event, item, index)"
            :class="[item.class, { 'active-route': checkActiveRoute(item) }]"
            tabindex="0"
            :href="route(item.to)"
            @mouseenter="onMouseEnter"
            v-tooltip.hover="(isSlim || isSlimPlus) && root && !isActiveMenu ? item.label : null"
        >
            <!-- <i :class="item.icon" class="layout-menuitem-icon"></i> -->
            <Icon :name="item.icon" class="layout-menuitem-icon"/>
            <span class="layout-menuitem-text">{{ item.label }}</span>
            <i class="pi pi-fw pi-angle-down layout-submenu-toggler" v-if="item.items"></i>
        </Link>

        <ul ref="subMenuRef" :class="{ 'layout-root-submenulist': root }" v-if="item.items && item.visible !== false">
            <AppMenuItem v-for="(child, i) in item.items" :key="child" :index="i" :item="child" :parentItemKey="itemKey" :root="false"></AppMenuItem>
        </ul>
    </li>
</template>
