import { Menu, Role, Team } from "@/types";
import { TreeNode } from "primevue/treenode";

export interface NestedMenu extends Menu {
    children?: Menu[]
}

export interface MenuPermission {
    uuid: string;
    label: string;
    route_name?: string;
    name: string;
}

export interface RoleFormProps {
    pageTitle?: string;
    role?: Role;
    teams: Team[];
    menu: TreeNode[];
    menu_permissions: MenuPermission[];
    total_menu: number;
}

export interface RoleForm {
    _method: string;
    team_uuid?: string | null;
    label?: string | null;
    is_active?: boolean;
    permissions: string[];
    [key: string]: any;
}

export interface CheckboxState {
    checked: boolean;
    partialChecked: boolean
}

export interface TreeCheckboxEvent extends Event {
    node: any;
    target: any;
    checked: boolean;
}