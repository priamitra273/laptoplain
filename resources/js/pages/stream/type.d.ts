import { Cctv } from "@/types";

export interface Stream extends Cctv {
    status_stream_uuid: string;
    status_stream_name: string;
    mac_address: string;
    ip_dhcp: string;
    ip_static?: string;
    ip_flussonic?: string;
    link_rtsp?: string;
    link_embed?: string;
    link_embed_nonrelay?: string;
    updated_streaming_at?: string;
}

export interface Form {
    cctv_name: string|null;
    status_stream_uuid: string|null;
    ip_static: string|null;
    ip_flussonic: string|null;
    link_rtsp: string|null;
    link_embed: string|null;
    link_embed_nonrelay: string|null;
    [key: string]: any;
}