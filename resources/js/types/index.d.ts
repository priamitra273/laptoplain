import type { PageProps } from '@inertiajs/core';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface SidebarMenuItem {
    label: string;
    icon: string;
    to?: string; // 'to' is optional since not all items may have it (e.g., categories without links) and if provide it must be a route name in laravel
    items?: MenuItem[] | null; // sub-items are optional as well
}

export interface Auth {
    user: User;
    menu: SidebarMenuItem[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData extends PageProps {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
}

export interface PaginationMetaLink {
    active: boolean | null;
    url: string | null;
    label: string | null;
}

export interface Pagination {
    links: {
        first: string | null;
        last: string | null;
        next: string | null;
        prev: string | null;
    };
    meta: {
        current_page: number;
        from: number;
        last_page: number;
        links: PaginationMetaLink[];
        path: string;
        per_page: number;
        to: number;
        total: number;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface UserList extends User {
    uuid: string;
    team_uuid: string;
    team_name: string;
    role_id: number;
    role_label: string;
}

export interface Menu {
    uuid: string;
    label: string;
    parent?: string;
    parent_uuid?: string;
    icon: string;
    route?: string;
    sequence_number: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: any;
}

export interface Team {
    uuid: string;
    name: string;
    created_at?: string;
    updated_at?: string;
}

export interface Department {
    uuid: string;
    name: string;
    created_at?: string;
    updated_at?: string;
}

export interface RoleList {
    id: number;
    name: string;
    team_name: string;
    is_active: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Role {
    id: number;
    label: string;
    team_uuid: string;
    is_active: boolean;
    permissions: string[];
}

export interface Project {
    uuid: string;
    name: string;
    start_date: string;
    finish_date: string;
    plan_site: number;
    plan_cctv: number;
    created_at?: string;
    updated_at?: string;
}

export interface ProjectPriority {
    id: number
    name: string
    severity: string
    owned_id: number
    created_by?: string
    updated_by?: string
    deleted_by?: string
}

export interface AnalyticServer {
    id: number;
    uuid: string;
    ip_address: string;
    lisence: string;
    is_alive: boolean;
    is_active: boolean;
    created_by: number;
    updated_by: number;
    deleted_by: number | null;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    cpu_id: number | null;
    gpu_id: number | null;
    ram_id: number | null;
    ssd_id: number | null;
    mobo_id: number | null;
    nic_id: number | null;
    psu_id: number | null;
    lc_id: number | null;

    cpu?: Hardware | null;
    gpu?: Hardware | null;
    ram?: Hardware | null;
    ssd?: Hardware | null;
    motherboard?: Hardware | null;
    nic?: Hardware | null;
    psu?: Hardware | null;
    lc?: Hardware | null;

    // alias for motherboard
    mobo?: Hardware | null;
}

export interface Hardware {
    uuid: string;
    po_number: string;
    serial_number: string;
    category?: string;
    category_uuid?: string;
    brand: HardwareBrand | null;
    status: HardwareStatus;
    remarks: string | null;
    model?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface HardwareComponent {
    uuid: string;
    code: string;
    name: string;
    created_at?: string;
    updated_at?: string;
}

export interface HardwareBrand {
    uuid: string;
    name: string;
    created_at?: string;
}

export interface HardwareModel {
    brand_uuid: string;
    component_uuid: string;
    model: string;
}

export interface HardwareStatus {
    uuid: string;
    level: 1 | 2 | 3 | 4 | 5;
    name: string;
    description: string;
}

export interface Device {
    uuid: string;
    mac_address: string;
    ip_dhcp: string;
    ip_static?: string | null;
    created_at?: string;
    updated_at?: string;
}

export interface Site {
    uuid: string;
    site_id: number;
    site_name: string;
    latitude: number;
    longitude: number;
    status_uuid: string;
    status: string;
    project_uuid?: string;
    project_name?: string;
    regency_uuid?: string;
    regency_name?: string;
    replacement_site?: number;
    replacement_to?: string;
    has_preconfig?: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface Cctv {
    uuid: string;
    name: string;
    site_id: number;
    site_name: string;
    latitude: number;
    longitude: number;
    project_name: string;
    status_site: string;
    regency_name: string;
    created_at?: string;
    updated_at?: string;
}

export interface WorkProgress {
    done: number;
    pending: number;
    total: number;
}

export interface Progress {
    total_site: number;
    total_preconfig: number;
    installation: WorkProgress;
    stream_config: WorkProgress;
    analytic_config: WorkProgress;
}

export interface Statistic {
    uuid: string;
    name: string;
    plan_site: number;
    plan_cctv: number;
    start_date: string;
    finish_date: string;
    progress: Progress;
}

export interface MsProjectStatus {
    id: number;
    name: string;
    severity: string;
    owned_id?: number;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
