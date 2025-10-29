import { Device, Site } from "@/types";

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

export interface Preconfig extends Site {
    cctv?: PreconfigCamera[];
    total_cctv?: number;
    last_update?: string;
}

export interface FormProps {
    devices: Device[];
    preconfig: Preconfig;
}