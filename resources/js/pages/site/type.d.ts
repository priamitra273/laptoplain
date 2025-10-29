import { Device, Project, Site as MasterSite } from "@/types";

export interface SiteStatus {
    uuid: string;
    name: string;
}

export interface Regency {
    uuid: string;
    name: string;
}

export interface PreconfigCamera {
    uuid?: string | null;
    cctv_name: string | null;
    device_id: string | null;
    mac_address: string | null;
    ip_dhcp?: string | null;
}

export interface Site extends MasterSite {
    cctv?: PreconfigCamera[];
}

export interface Attachment {
    name: string;
    url: string;
}

export interface SiteHistory {
    status: string;
    remark: string;
    attachments?: Attachment[];
    created_at: string;
    created_by: string;
}

export interface SiteFormProps {
    site?: Site;
    pageTitle?: string;
    regencies: Regency[];
    dismantle_sites: Site[];
    projects: Project[];
}

export interface SiteForm {
    _method: 'POST' | 'PUT';
    site_id: numeric | null;
    site_name: string | null;
    latitude: number | null;
    longitude: number | null;
    project_id: string | null;
    regency_id: string | null;
    remark?: string;
    site_status_id: string | null;
    site_category: 'New' | 'Replacement';
    replacement_to: string | null;
    remark: string | null;
    attachments: (string | File)[],
    cctv: PreconfigCamera[],
    [key: string]: any;
}

export interface SiteFormNew {
    _method: 'POST' | 'PUT';
    site_id: numeric | null;
    site_name: string | null;
    latitude: number | null;
    longitude: number | null;
    project_id: string | null;
    regency_id: string | null;
    site_category: 'New' | 'Replacement';
    replacement_to: string | null;
    [key: string]: any;
}