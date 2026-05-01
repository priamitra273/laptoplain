import type { PageProps as InertiaPageProps } from '@inertiajs/core';
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
    role: string;
    menu: SidebarMenuItem[];
    permissions: string[];
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

declare module '@inertiajs/core' {
    export interface PageProps extends InertiaPageProps {
        name: string;
        quote: { message: string; author: string };
        auth: Auth;
        flash: { success: string | null; error: string | null };
        ziggy: Config & { location: string };
    }
}

export interface SharedData extends InertiaPageProps {
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

export interface PaginationLink {
    active: boolean | null;
    url: string | null;
    label: string | null;
    page: number | null;
}

export interface PaginationMeta {
    current_page: number;
    first_page_url: string | null;
    from: number;
    last_page: number;
    last_page_url: string | null;
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface LengthAwarePaginator<T> {
    data: T[];
    meta: PaginationMeta;
    links: PaginationLink[];
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

export type PrimeSeverity = 'primary' | 'secondary' | 'success' | 'info' | 'warn' | 'danger' | 'contrast';

export interface User {
    id: string;
    name: string;
    email: string;
    avatar_url?: string;
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
    created_by: string;
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
    id: number;
    encoded: string;
    emoji: string;
    title: string;
    description: string;
    start_date: string;
    due_date: string;
    progress: number;
    sequence_number: number;
    status_id: number;
    priority_id: number;
    owner_id: number;
    owned_id: number;
    created_by: string;
    updated_by: string;
    created_at: string;
    updated_at: string;
    status: {
        id: string;
        name: string;
        severity: PrimeSeverity;
    };
    priority: {
        id: string;
        name: string;
        severity: PrimeSeverity;
    };
    // owner?: User;
    project_members: {
        id: string;
        user_id: string;
        role_id: string;
        user: {
            name: string;
        };
        role: {
            name: string;
        };
    }[];
}

export interface Tag {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id: number;
    created_by?: string;
    updated_by?: string;
    deleted_by?: string;
}

export interface TaskType {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id: number;
    created_by?: string;
    updated_by?: string;
    deleted_by?: string;
}

export interface TaskStatus {
    id: number;
    name: string;
    severity: PrimeSeverity;
    score: number;
    owned_id: number;
    created_by?: string;
    updated_by?: string;
    deleted_by?: string;
}

export interface ProjectPriority {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id: number;
    created_by?: string;
    updated_by?: string;
    deleted_by?: string;
}

export interface ProjectRoleConfig {
    task?: string[];
    project_member?: string[];
    sprint?: string[];
    allow_task_status?: string[];
    allow_update_task_fields?: string[];
}

export interface ProjectRole {
    id: number;
    name: string;
    config?: ProjectRoleConfig;
    owned_id: number;
    created_by?: string;
    updated_by?: string;
    deleted_by?: string;
}

export interface TaskPriority {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id: number;
    created_by?: string;
    updated_by?: string;
    deleted_by?: string;
}

export interface TaskCategory {
    id: number;
    name: string;
    icon: string;
    severity: PrimeSeverity;
    created_by?: string;
    updated_by?: string;
    deleted_by?: string;
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

export interface TaskStatistic {
    totalTasks: number;
    completed: number;
    inProgress: number;
    notStarted: number;
}

export interface MsProjectStatus {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id?: number;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
}

export interface MsProjectPriority {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id?: number;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
}

export interface MsTaskStatus {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id?: number;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
}

export interface MsTaskType {
    id: number;
    name: string;
    severity: PrimeSeverity;
    owned_id?: number;
    created_at?: string;
    updated_at?: string;
    deleted_at?: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface SeverityOption {
    label: string;
    value: PrimeSeverityEnum;
}

export interface Notification {
    id: string;
    message: string;
    task_id: string;
    is_read: boolean;
}

export interface BaseOption {
    id: string;
    name: string;
    severity?: PrimeSeverity;
}

export interface StatusOption extends BaseOption {}
export interface PriorityOption extends BaseOption {}

export interface ProjectRoleConfig {
    task?: string[];
    sprint?: string[];
    project_member?: string[];
    allow_task_status?: number[];
    allow_update_task_fields?: string[];
}

export interface ProjectRoleOption {
    id: string;
    name: string;
    config?: ProjectRoleConfig | null;
}

export interface UploadedFile {
    uuid: string;
    file_name: string;
    size: number;
    mime_type: string;
    url: string;
    created_at: string;
    updated_at: string;
}
