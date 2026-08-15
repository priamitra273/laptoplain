<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { TreeNode } from 'primevue/treenode';
import { CheckboxState, MenuPermission, TreeCheckboxEvent } from '../type';
import Icon from '@/components/Icon.vue';
import Heading from '@/components/Heading.vue';
import TreeColumnCheckbox from './TreeColumnCheckbox.vue';
import { findNode, nodePropagateDown, nodePropagateUp } from '../function';
import { watchDebounced } from '@vueuse/core';

interface Props {
    modelValue: string[];
    menu: TreeNode[];
    menuPermissions: MenuPermission[];
    totalMenu: number;
}

interface Emits {
    (event: 'update:modelValue', value: string[]): void;
}

const props = defineProps<Props>()
const emits = defineEmits<Emits>()

const viewPermissions = ref<Record<string, CheckboxState>>({})
const createPermissions = ref<Record<string, CheckboxState>>({})
const updatePermissions = ref<Record<string, CheckboxState>>({})
const deletePermissions = ref<Record<string, CheckboxState>>({})

const selectedRows = ref<Record<string, CheckboxState>>({})

const permissions = computed({
    get: () => props.modelValue,
    set: (value) => emits('update:modelValue', value)
})

const countSelectedKeys = computed<number>(() => {
    const view = Object.keys(viewPermissions.value).length;
    const create = Object.keys(createPermissions.value).length;
    const update = Object.keys(updatePermissions.value).length;
    const destroy = Object.keys(deletePermissions.value).length;

    return view + create + update + destroy;
})

const onChangeRowCheckbox = (check: boolean, node: any) => {
    changeSelectedKeys(check, node, viewPermissions.value);
    changeSelectedKeys(check, node, createPermissions.value);
    changeSelectedKeys(check, node, updatePermissions.value);
    changeSelectedKeys(check, node, deletePermissions.value);
}

const changeSelectedKeys = (check: boolean, node: any, selectedKeys: Record<string, CheckboxState>): void => {
    nodePropagateDown(node, check, selectedKeys)
    nodePropagateUp(node, check, selectedKeys, props.menu)
}

const onChangeCheckboxWithView = (event: TreeCheckboxEvent): void => {
    const check: boolean = (event.target as HTMLInputElement).checked;

    if (check) {
        viewPermissions.value[event.node.key] = { checked: true, partialChecked: false }
        changeSelectedKeys(true, event.node, viewPermissions.value)
    }

    updateRowCheckbox(event)
}

const onChangeCheckboxWithOthers = (event: TreeCheckboxEvent): void => {
    const check: boolean = (event.target as HTMLInputElement).checked;

    if (!check) {
        delete updatePermissions.value[event.node.key];
        delete createPermissions.value[event.node.key];
        delete deletePermissions.value[event.node.key];

        changeSelectedKeys(false, event.node, updatePermissions.value);
        changeSelectedKeys(false, event.node, createPermissions.value);
        changeSelectedKeys(false, event.node, deletePermissions.value);
    }

    updateRowCheckbox(event)
}

const updateRowCheckbox = (event: TreeCheckboxEvent): void => {
    const view = viewPermissions.value[event.node.key]?.checked
    const update = updatePermissions.value[event.node.key]?.checked
    const create = createPermissions.value[event.node.key]?.checked
    const destroy = deletePermissions.value[event.node.key]?.checked

    rowPropagateDown(event.node, (view && update && create && destroy), (view || update || create || destroy))
    rowPropagateUp(props.menu, event.node, (view && update && create && destroy), (view || update || create || destroy))
}

const onChangeHeaderCheckbox = (event: Event, isViewPermission: boolean) => {
    const check: boolean = (event.target as HTMLInputElement).checked;

    if (isViewPermission && !check) {
        for (const menu of props.menu) {
            nodePropagateDown(menu, false, updatePermissions.value)
            nodePropagateDown(menu, false, createPermissions.value)
            nodePropagateDown(menu, false, deletePermissions.value)
        }
    }
    else if (!isViewPermission && check) {
        for (const menu of props.menu) {
            nodePropagateDown(menu, true, viewPermissions.value)
        }
    }

    headerPropagate(props.menu)
}

const headerPropagate = (nodes: TreeNode[]) => {
    for (const node of nodes) {
        const view = viewPermissions.value[node.key]?.checked
        const update = updatePermissions.value[node.key]?.checked
        const create = createPermissions.value[node.key]?.checked
        const destroy = deletePermissions.value[node.key]?.checked

        const checked = (view && update && create && destroy)
        const partialChecked = (view || update || create || destroy)

        if (checked) selectedRows.value[node.key] = { checked: true, partialChecked: false };
        else if (partialChecked) selectedRows.value[node.key] = { checked: false, partialChecked: true };
        else delete selectedRows.value[node.key];

        if (node.children && node.children.length) {
            headerPropagate(node.children)
        }
    }
}

const rowPropagateDown = (node: any, checked: boolean, partialChecked: boolean): void => {
    if (checked) selectedRows.value[node.key] = { checked: true, partialChecked: false };
    else if (partialChecked) selectedRows.value[node.key] = { checked: false, partialChecked: true };
    else delete selectedRows.value[node.key];

    if (node.children && node.children.length) {
        for (const child of node.children) {
            rowPropagateDown(child, checked, partialChecked);
        }
    }
}

const rowPropagateUp = (nodes: any[], node: any, checked: boolean, partialChecked: boolean) => {
    let checkedChildCount = 0;
    let childPartialSelected = false;

    if (node.data.parent_uuid) {
        const parentNode = findNode(nodes, node.data.parent_uuid)

        for (const child of parentNode.children) {
            if (selectedRows.value[child.key] && selectedRows.value[child.key].checked) checkedChildCount++;
            else if (selectedRows.value[child.key] && selectedRows.value[child.key].partialChecked) childPartialSelected = true;
        }

        if (checked && checkedChildCount === parentNode.children.length) {
            selectedRows.value[parentNode.key] = { checked: true, partialChecked: false };
        } else {
            if (!checked) {
                delete selectedRows.value[parentNode.key];
            }

            if (childPartialSelected || (checkedChildCount > 0 && checkedChildCount !== parentNode.children.length)) {
                selectedRows.value[parentNode.key] = { checked: false, partialChecked: true };
            }
            else {
                selectedRows.value[parentNode.key] = { checked: false, partialChecked: false };
            }
        }

        rowPropagateUp(nodes, parentNode, checked, partialChecked)
    }
}

const onChangeRowHeaderCheckbox = (event: Event): void => {
    const check = (event.target as HTMLInputElement).checked

    if (check) {
        for (const menu of props.menu) {
            selectCheckbox(menu)
        }
    }
    else {
        viewPermissions.value = {}
        createPermissions.value = {}
        updatePermissions.value = {}
        deletePermissions.value = {}
    }
}

const selectCheckbox = (node: TreeNode) => {
    viewPermissions.value[node.key] = { checked: true, partialChecked: false }
    createPermissions.value[node.key] = { checked: true, partialChecked: false }
    updatePermissions.value[node.key] = { checked: true, partialChecked: false }
    deletePermissions.value[node.key] = { checked: true, partialChecked: false }

    if (node.children && node.children.length) {
        for (const child of node.children) {
            selectCheckbox(child)
        }
    }
}

const filterSelectedCheckbox = (records: Record<string, CheckboxState>, state: string): string[] => {
    const menuKeys: string[] = []

    for (const key in records) {
        if (records[key].checked || records[key].partialChecked) {
            menuKeys.push(key)
        }
    }

    return [
        ...props.menuPermissions
            .filter((item) => menuKeys.includes(item.uuid) && item.name.endsWith(state))
            .map((item) => item.name)
    ]
}

watchDebounced(countSelectedKeys, () => {
    permissions.value = [
        ...filterSelectedCheckbox(viewPermissions.value, '.read'),
        ...filterSelectedCheckbox(createPermissions.value, '.create'),
        ...filterSelectedCheckbox(updatePermissions.value, '.update'),
        ...filterSelectedCheckbox(deletePermissions.value, '.delete'),
    ]
}, { debounce: 300, maxWait: 1000 })

onMounted(() => {
    for (const permission of permissions.value) {
        const menu = props.menuPermissions.find((item) => item.name === permission)
        const node = findNode(props.menu, menu?.uuid ?? '');

        if (menu && node && menu.route_name) {
            if (permission.endsWith('.read')) {
                viewPermissions.value[menu.uuid] = { checked: true, partialChecked: false }
                changeSelectedKeys(true, node, viewPermissions.value)
            }
            else if (permission.endsWith('.create')) {
                createPermissions.value[menu.uuid] = { checked: true, partialChecked: false }
                changeSelectedKeys(true, node, createPermissions.value)
            }
            else if (permission.endsWith('.update')) {
                updatePermissions.value[menu.uuid] = { checked: true, partialChecked: false }
                changeSelectedKeys(true, node, updatePermissions.value)
            }
            else if (permission.endsWith('.delete')) {
                deletePermissions.value[menu.uuid] = { checked: true, partialChecked: false }
                changeSelectedKeys(true, node, deletePermissions.value)
            }

            const view = viewPermissions.value[menu.uuid]?.checked
            const update = updatePermissions.value[menu.uuid]?.checked
            const create = createPermissions.value[menu.uuid]?.checked
            const destroy = deletePermissions.value[menu.uuid]?.checked

            rowPropagateDown(node, (view && update && create && destroy), (view || update || create || destroy))
            rowPropagateUp(props.menu, node, (view && update && create && destroy), (view || update || create || destroy))
        }
    }

})

</script>

<template>
    <Card>
        <template #content>
            <Heading title="Role's Permissions" description="Please choose at least one permission." />

            <TreeTable :value="props.menu" row-hover>
                <Column field="label" header="Menu" expander class="w-1/2"></Column>
                <Column field="icon" header="Icon">
                    <template #body="{ node }">
                        <Icon :name="node.data.icon" class="size-5" />
                    </template>
                </Column>

                <TreeColumnCheckbox key="view" v-model="viewPermissions" :nodes="props.menu" header="View"
                    :total-node="props.totalMenu" input-name="view-checkbox" input-header-name="header-view-checkbox"
                    @checkbox-change="onChangeCheckboxWithOthers"
                    @checkbox-header-change="onChangeHeaderCheckbox($event, true)" />

                <TreeColumnCheckbox key="create" v-model="createPermissions" :nodes="props.menu" header="Create"
                    :total-node="props.totalMenu" input-name="create-checkbox"
                    input-header-name="header-create-checkbox" @checkbox-change="onChangeCheckboxWithView"
                    @checkbox-header-change="onChangeHeaderCheckbox($event, false)" />

                <TreeColumnCheckbox key="update" v-model="updatePermissions" :nodes="props.menu" header="Update"
                    :total-node="props.totalMenu" input-name="update-checkbox"
                    input-header-name="header-update-checkbox" @checkbox-change="onChangeCheckboxWithView"
                    @checkbox-header-change="onChangeHeaderCheckbox($event, false)" />

                <TreeColumnCheckbox key="delete" v-model="deletePermissions" :nodes="props.menu" header="Delete"
                    :total-node="props.totalMenu" input-name="delete-checkbox"
                    input-header-name="header-delete-checkbox" @checkbox-change="onChangeCheckboxWithView"
                    @checkbox-header-change="onChangeHeaderCheckbox($event, false)" />

                <TreeColumnCheckbox key="row" v-model="selectedRows" :nodes="props.menu" :total-node="props.totalMenu"
                    input-name="row-checkbox" input-header-name="header-row-checkbox"
                    @checkbox-change="onChangeRowCheckbox($event.checked, $event.node)"
                    @checkbox-header-change="onChangeRowHeaderCheckbox" />
            </TreeTable>
        </template>
    </Card>
</template>