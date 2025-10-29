import { Cctv } from "@/types";

export interface WaterSurfaceThreshold {
    level_1: number|null;
    level_2: number|null;
    level_3: number|null;
    [key: string]: number|null;
}

export interface WaterLevelThreshold {
    level_1: number|null;
    level_2: number|null;
    level_3: number|null;
    [key: string]: number|null;
}

export interface CrowdDetectionThreshold {
    people: number|null;
    vehicle: number|null;
    street_vendor: number|null;
    [key: string]: number|null;
}

export interface Category {
    uuid: string;
    type: string;
    name: string;
    has_threshold: boolean;
    has_polygon: boolean;
    created_at: string;
    updated_at?: string;
}

export interface Analytic extends Cctv {
    department_uuid?: string|null;
    department_name?: string|null;
    ip_static?: string|null;
    ip_flussonic?: string|null;
    server_uuid?: string|null;
    ip_server?: string|null;
    link_rtsp?: string|null;
    link_embed?: string|null;
    link_embed_nonrelay?: string|null;
    link_embed_bb?: string|null;
    category_uuid?: string|null;
    category_name?: string|null;
    analytic_status_uuid?: string|null;
    analytic_status_name?: string|null;
    polygon?: number[][];
    threshold?: WaterSurfaceThreshold | WaterLevelThreshold | CrowdDetectionThreshold | null;
    updated_analytic_at?: string|null;
}

export interface Form {
    category_uuid: string|null;
    department_uuid: string|null;
    server_uuid: string|null;
    link_embed_bb: string|null;
    polygon: number[][];
    threshold: WaterLevelThreshold | WaterLevelThreshold | CrowdDetectionThreshold | null;
    [key: string]: any;
}