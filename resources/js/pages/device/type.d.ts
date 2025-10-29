import { Device } from "@/types";

export interface DeviceExtended extends Device {
    site_id?: number|null;
    cctv_name?: string|null;
}